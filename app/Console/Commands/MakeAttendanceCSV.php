<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class MakeAttendanceCSV extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:make-single-csv';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs the function inside the LeaveController every hour';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
        $dataFile = public_path('csv/dataset.csv');
        $attendanceDirectory = public_path('excels');
        // Check if the attendance directory exists
        if (!is_dir($attendanceDirectory)) {
            Log::error("Attendance directory not found: $attendanceDirectory");
            return response()->json(['status' => 'error', 'message' => 'Attendance directory not found'], 404);
        }

        $files = scandir($attendanceDirectory);
        $attendanceFiles = array_filter($files, fn($file) => preg_match("/^\d+\.csv$/", $file));

        if (empty($attendanceFiles)) {
            return response()->json(['status' => 'error', 'message' => 'No attendance files found'], 404);
        }

        $allRows = collect();
        $existingData = [];

        // Step 1: Read existing dataset.csv to prevent duplicate entries per day
        if (File::exists($dataFile)) {
            if (($handle = fopen($dataFile, 'r')) !== false) {
                $headers = fgetcsv($handle);
                while (($row = fgetcsv($handle)) !== false) {
                    // Check if the number of columns in the row matches the header
                    if (count($headers) == count($row)) {
                        $rowAssoc = array_combine($headers, $row);
                        $acNo = $rowAssoc['Ac-No'];
                        $dateOnly = Carbon::createFromFormat('d/m/Y H:i:s', $rowAssoc['formattedDate'])->format('d/m/Y'); // Extract only date part
                        $existingData["$acNo|$dateOnly"] = true; // Store Ac-No & Date to prevent duplicates
                    } else {
                        Log::warning("Skipping row due to column mismatch in $dataFile.");
                    }
                }
                fclose($handle);
            }
        }

        // Step 2: Process each CSV file
        foreach ($attendanceFiles as $fileName) {
            $filePath = $attendanceDirectory . '/' . $fileName;
            Log::info("Processing file: $filePath");

            if (($handle = fopen($filePath, 'r')) !== false) {
                $headers = fgetcsv($handle);
                Log::info("Headers in $fileName: " . implode(', ', $headers));

                while (($row = fgetcsv($handle)) !== false) {
                    // Check if the number of columns in the row matches the header
                    if (count($headers) != count($row)) {
                        Log::warning("Skipping row due to column mismatch in file: $fileName.");
                        continue; // Skip this row if the number of columns doesn't match
                    }

                    // Now it's safe to combine the row with the headers
                    $rowAssoc = array_combine($headers, $row);

                    if (!isset($rowAssoc['sTime']) || !isset($rowAssoc['Ac-No'])) {
                        Log::warning("Missing required columns in file: $fileName");
                        continue;
                    }

                    try {
                        $fullDate = Carbon::createFromFormat('d/m/Y h:i A', $rowAssoc['sTime']);
                        $dateOnly = $fullDate->format('d/m/Y'); // Extract only the date
                        $rowAssoc['formattedDate'] = $fullDate->format('d/m/Y H:i:s'); // Standardized format
                    } catch (\Exception $e) {
                        Log::error("Invalid date format in $fileName: " . $rowAssoc['sTime']);
                        continue;
                    }

                    $acNo = $rowAssoc['Ac-No'];
                    $uniqueKey = "$acNo|$dateOnly";

                    // Step 3: Check for duplicate entry per Ac-No per day
                    if (!isset($existingData[$uniqueKey])) {
                        $allRows->push($rowAssoc);
                        $existingData[$uniqueKey] = true; // Mark as existing
                    } else {
                        Log::info("Duplicate entry for Ac-No $acNo on $dateOnly skipped.");
                    }
                }
                fclose($handle);
            } else {
                Log::error("Could not open file: $filePath");
            }
        }

        // Step 4: Append new unique data to dataset.csv
        if ($allRows->isNotEmpty()) {
            $fileExists = File::exists($dataFile);
            $fp = fopen($dataFile, 'a');

            if (!$fileExists) {
                fputcsv($fp, array_keys($allRows->first())); // Write headers if file is new
            }

            foreach ($allRows as $row) {
                fputcsv($fp, $row);
            }
            fclose($fp);

            Log::info("Data successfully written to dataset.csv");
        } else {
            Log::warning("No new data found to append.");
        }
    }
}
