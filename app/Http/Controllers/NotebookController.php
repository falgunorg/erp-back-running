<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notebook;
use App\Models\NotebookFile;
use Carbon\Carbon;
use App\Models\User;

class NotebookController extends Controller {

    public function index(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $limit = 10;
            $per_page = $request->input('perPage') ?? 12;
            $page_num = $request->input('page');
            $start = ($page_num - 1) * $per_page;
            $user_id = $request->user->id;
            $sort_by = $request->input('sort_by');
            $sort_order = $request->input('sort_order');
            $year = $request->input('year');

            $notebooks = Notebook::where(function ($query) use ($user_id) {
                        $query->where('user_id', $user_id)
                                ->orWhere('attention_to', $user_id);
                    });

            if ($year) {
                $notebooks = $notebooks->whereYear('created_at', '=', $year);
            }

            $notebooks = $notebooks->getQuery();

            if ($sort_order === 'newest_to_oldest') {
                $notebooks = $notebooks->orderBy('created_at', 'desc');
            } elseif ($sort_order === 'oldest_to_newest') {
                $notebooks = $notebooks->orderBy('created_at', 'asc');
            }

            if ($sort_by === 'az') {
                $notebooks = $notebooks->orderByRaw("LOWER(title) ASC");
            } elseif ($sort_by === 'za') {
                $notebooks = $notebooks->orderByRaw("LOWER(title) DESC");
            }

            $totalCount = $notebooks->count();

            $data = $notebooks->orderBy('created_at', 'desc')
                    ->skip($start)
                    ->take($per_page)
                    ->get();

            if ($data) {
                foreach ($data as $val) {
                    $user = User::where('id', $val->user_id)->first();
                    $val->user_photo = url('') . '/profile_pictures/' . $user->photo;
                    $val->user_name = $user->full_name;
                    $attention_user = User::where('id', $val->attention_to)->first();
                    $val->attention_to_username = $attention_user->full_name;
                }

                $return['data'] = $data;
                $return['totalCount'] = $totalCount;
                $page_count = ceil($totalCount / $per_page);
                $prevCount = (($page_num - 1) * $per_page) + count($data);
                $return['loadedCount'] = $prevCount;
                $return['paginationData'] = \App\Libraries\Paginator::paginateData($page_count, $page_num);
                $statusCode = 200;
            } else {
                $return['status'] = 'error';
            }

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function index_bk(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $limit = 10;
            $per_page = $request->input('perPage') ?? 12;
            $page_num = $request->input('page');
            $start = ($page_num - 1) * $per_page;
            $user_id = $request->user->id;
            $sort_by = $request->input('sort_by');
            $sort_order = $request->input('sort_order');
            $year = $request->input('year');

            $notebooks = Notebook::where(function ($query) use ($user_id) {
                        $query->where('user_id', $user_id)
                                ->orWhere('attention_to', $user_id);
                    });

            if ($year) {
                $notebooks = $notebooks->whereYear('created_at', '=', $year);
            }

            if ($sort_by === 'az') {
                $notebooks = $notebooks->orderBy('title');
            } elseif ($sort_by === 'za') {
                $notebooks = $notebooks->orderBy('title', 'desc');
            }

            if ($sort_order === 'newest_to_oldest') {
                $notebooks = $notebooks->orderBy('created_at', 'desc');
            } elseif ($sort_order === 'oldest_to_newest') {
                $notebooks = $notebooks->orderBy('created_at', 'asc');
            }

            $totalCount = $notebooks->count();

            $data = $notebooks->orderBy('created_at', 'desc')
                    ->skip($start)
                    ->take($per_page)
                    ->get();

            if ($data) {
                foreach ($data as $val) {
                    $user = User::where('id', $val->user_id)->first();
                    $val->user_photo = url('') . '/profile_pictures/' . $user->photo;
                    $val->user_name = $user->full_name;
                    $attention_user = User::where('id', $val->attention_to)->first();
                    $val->attention_to_username = $attention_user->full_name;
                }

                $return['data'] = $data;
                $return['totalCount'] = $totalCount;
                $page_count = ceil($totalCount / $per_page);
                $prevCount = (($page_num - 1) * $per_page) + count($data);
                $return['loadedCount'] = $prevCount;
                $return['paginationData'] = \App\Libraries\Paginator::paginateData($page_count, $page_num);
                $statusCode = 200;
            } else {
                $return['status'] = 'error';
            }

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function store(Request $request) {


        try {
            $statusCode = 422;
            $return = [];
//           input_variable
            $user_id = $request->user->id;
            $title = $request->input('title');
            $message = $request->input('message');
            $attention_to = $request->input('attention_to');

            $note = new Notebook;
            $note->user_id = $user_id;

            if (strlen($title) == 0) {
                $return['errors']['title'] = 'Please insert title';
            } else {
                $note->title = $title;
            }
            if (strlen($attention_to) == 0) {
                $return['errors']['attention_to'] = 'Please insert attention to';
            } else {
                $note->attention_to = $attention_to;
            }


            $note->message = $message;

            if (!isset($return['errors'])) {
                if ($note->save()) {
                    if (request()->hasFile('attatchments')) {
                        $files = request()->file('attatchments');
                        foreach ($files as $file) {
                            $upload = new NotebookFile;
                            $upload->notebook_id = $note->id;
                            $resource_path = public_path();
                            $path = $resource_path . '/' . "notebooks";

                            $pathinfo = pathinfo($file->getClientOriginalName());
                            $basename = strtolower(str_replace(' ', '_', $pathinfo['filename']));
                            $extension = strtolower($pathinfo['extension']);

                            $file_name = $basename . '.' . $extension;
                            $finalpath = $path . '/' . $file_name;

                            if (file_exists($finalpath)) {
                                $file_name = $basename . '_' . time() . '.' . $extension;
                                $finalpath = $path . '/' . $file_name;
                            }

                            if ($file->move($path, $file_name)) {
                                $upload->filename = $file_name;
                                $upload->save();
                            }
                        }
                    }
                    $return['data'] = $note;
                    $statusCode = 200;
                    $return['status'] = 'success';
                } else {
                    $return['errors']['main_error_message'] = 'Saving error';
                    $return['status'] = 'error';
                }
            } else {
                $return['status'] = 'error';
            }



















            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function show(Request $request) {
        try {
            $statusCode = 422;
            $return = [];

            $id = $request->input('id');

            $notebook = Notebook::find($id);

            if ($notebook) {
                $attachments = NotebookFile::where('notebook_id', $notebook->id)->get();
                foreach ($attachments as $val) {
                    $val->file_source = url('') . '/notebooks/' . $val->filename;
                }
                $notebook->attachments = $attachments;
                $user = \App\Models\User::where('id', $notebook->user_id)->first();
                $notebook->user_photo = url('') . '/profile_pictures/' . $user->photo;
                $notebook->user_name = $user->full_name;
                $attention_user = \App\Models\User::where('id', $notebook->attention_to)->first();
                $notebook->attention_to_username = $attention_user->full_name;

                $return['data'] = $notebook;
                $statusCode = 200;
                $return['status'] = 'success';
            } else {
                $return['status'] = 'error';
            }
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function update(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
//           input_variable
            $user_id = $request->user->id;
            $title = $request->input('title');
            $message = $request->input('message');
            $attention_to = $request->input('attention_to');
            $id = $request->input('id');
            $note = Notebook::find($id);
            $note->user_id = $user_id;

            if (strlen($title) == 0) {
                $return['errors']['title'] = 'Please insert title';
            } else {
                $note->title = $title;
            }
            if (strlen($attention_to) == 0) {
                $return['errors']['attention_to'] = 'Please insert attention to';
            } else {
                $note->attention_to = $attention_to;
            }

            if (strlen($message) == 0) {
                $return['errors']['message'] = 'Please insert message';
            } else {
                $note->message = $message;
            }

            if (!isset($return['errors'])) {
                if ($note->save()) {
                    if ($_FILES['attatchments']['name']) {

                        $old_attachments = NotebookFile::where('notebook_id', $note->id)->delete();
                        $upload = new NotebookFile;
                        $upload->notebook_id = $note->id;
                        $resource_path = public_path();
                        $path = $resource_path . '/' . "notebooks";
                        $pathinfo = pathinfo($_FILES['attatchments']['name']);

                        $basename = strtolower(str_replace(' ', '_', $pathinfo['filename']));
                        $extension = strtolower($pathinfo['extension']);

                        $file_name = $basename . '.' . $extension;

                        $finalpath = $path . '/' . $file_name;
                        if (file_exists($finalpath)) {
                            $file_name = $basename . time() . '.' . $extension;
                            $finalpath = $path . '/' . $file_name;
                        }
                        if (move_uploaded_file($_FILES['attatchments']['tmp_name'], $finalpath)) {
                            $upload->filename = $file_name;
                            $upload->save();
                        }
                    }
//
//                    if (request()->hasFile('attatchments')) {
//                        $files = request()->file('attatchments');
//                        foreach ($files as $file) {
//                            $upload = new AnnouncementFile;
//                            $upload->announcement_id = $note->id;
//                            $resource_path = public_path();
//                            $path = $resource_path . '/' . "announcements";
//                            $pathinfo = pathinfo($file->getClientOriginalName());
//                            $basename = strtolower(str_replace(' ', '_', $pathinfo['filename']));
//                            $extension = strtolower($pathinfo['extension']);
//                            $file_name = $basename . '.' . $extension;
//                            $finalpath = $path . '/' . $file_name;
//                            if (file_exists($finalpath)) {
//                                $file_name = $basename . time() . '.' . $extension;
//                                $finalpath = $path . '/' . $file_name;
//                            }
//                            if ($file->move($path, $file_name)) {
//                                $upload->filename = $file_name;
//                                $upload->save();
//                            }
//                        }
//                    }


                    $return['data'] = $note;
                    $return['attachments'] = NotebookFile::where('notebook_id', $note->id)->get();
                    $statusCode = 200;
                    $return['status'] = 'success';
                } else {
                    $return['errors']['main_error_message'] = 'Saving error';
                    $return['status'] = 'error';
                }
            } else {
                $return['status'] = 'error';
            }
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function destroy(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
//           input_variable\

            $id = $request->input('id');

            $find_note = Notebook::find($id);
            $delete = $find_note->delete();

            if ($delete) {
                $delete_attachment = NotebookFile::where('notebook_id', $find_note->id)->delete();
            }

            $statusCode = 200;
            $return['status'] = 'success';
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

}
