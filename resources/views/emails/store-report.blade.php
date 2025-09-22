<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Store Report</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 0;
                background-color: #f4f4f4;
            }
            h4 {
                margin-bottom: 1px;
                background: #ef9a3e;
                color: white;
                text-transform: uppercase;
                text-align: center;
                padding-top: 15px;
            }
            .container {
                max-width: 800px;
                margin: 0 auto;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                margin-top: 30px;
            }

            table {
                width: 100%;
                border-collapse: collapse;
            }
            th {
                padding: 10px;
                border-bottom: 1px solid #ddd;
                text-align: left;
                font-size: 12px;
                background-color: #ef9a3e;
            }

            td {
                padding: 10px;
                border-bottom: 1px solid #ddd;
                text-align: left;
                font-size: 12px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h5>Dear {{$username}},</h5>
            <p style="background: none; font-size: 13px">
                Here is Today's Store Transaction's Report, Please Check it out.
            </p>

            <div class="printable_area">
                <h4>
                    Daily Receive Report
                </h4>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Item</th>
                            <th>Requisition No</th>
                            <th>QTY</th>
                            <th>Received By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData['receives'] as $receive)
                        <tr>
                            <td>{{$receive->created_at}}</td>
                            <td>{{$receive->part_name}}</td>
                            <td>{{$receive->requsition_number}}</td>
                            <td>{{$receive->qty}} | {{$issue->unit}}</td>
                            <td>{{$receive->received_by}}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <br /><br />
                <h4>
                    Daily Issue Report
                </h4>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Item</th>
                            <th>Type</th>
                            <th>Line/Dept</th>
                            <th>Issue To</th>
                            <th>Reference</th>
                            <th>Remarks</th>
                            <th>QTY</th>
                            <th>Issue/Delivery By</th>
                        </tr>
                    </thead>
                    <tbody>

                        @foreach($reportData['issues'] as $issue)
                        <tr>
                            <td>{{$issue->created_at}}</td>
                            <td>{{$issue->part_name}}</td>
                            <td>{{$issue->type}}</td>
                            <td>{{$issue->line}}</td>
                            <td>{{$issue->issue_to_show}}</td>
                            <td>{{$issue->reference}}</td>
                            <td>{{$issue->remarks}}</td>
                            <td>{{$issue->qty}} | {{$issue->unit}}</td>
                            <td>{{$issue->issue_by}}</td>
                        </tr>
                        @endforeach


                    </tbody>
                </table>
                <br /><br />
                <h4>
                    Store Summary
                </h4>
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Unit</th>
                            <th>Received</th>
                            <th>Issued</th>
                            <th>Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData['summary'] as $store)
                        <tr>
                            <td>{{$store->part_name}}</td>
                            <td>{{$store->unit}}</td>
                            <td>{{$store->total_receives}}</td>
                            <td>{{$store->total_issues}}</td>
                            <td>{{$store->qty}}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </body>
</html>
