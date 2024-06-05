<?php

/**
 * plugin name: ارسال پیامک گروهی 
 * 
 */

require 'vendor/autoload.php';


function custom_sms_upload_form()
{
    $form_html = '<form action="' . esc_url($_SERVER['REQUEST_URI']) . '" method="post" enctype="multipart/form-data">
        Select Excel File to Upload:
        <input type="file" name="uploaded_file">
        <input type="submit" name="submit" value="Upload">
    </form>';

    if (isset($_POST['submit'])) {
        handle_excel_upload();
    }

    return $form_html;
}

add_shortcode('sms_upload_form', 'custom_sms_upload_form');

function handle_excel_upload()
{
    if (isset($_FILES['uploaded_file'])) {
        $allowedFileType = ['application/vnd.ms-excel', 'text/xls', 'text/xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];

        if (in_array($_FILES["uploaded_file"]["type"], $allowedFileType)) {
            $target_path = plugin_dir_path(__FILE__) . $_FILES['uploaded_file']['name'];
            move_uploaded_file($_FILES['uploaded_file']['tmp_name'], $target_path);

            send_sms_via_kavenegar($target_path);
        } else {
            echo '<div>Invalid File Type. Upload Excel files.</div>';
        }
    }
}

function send_sms_via_kavenegar($file_path)
{
    // require_once 'path/to/PhpSpreadsheet/vendor/autoload.php';  // Adjust the path as needed
    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
    $spreadsheet = $reader->load($file_path);
    $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

    $API_KEY = '2F733971636861506A566430794B63496A4E742B742F6253464F457934794358724C4C337674494A7663773D';
    $template = 'you-can-pay-now';

    foreach ($sheetData as $row) {
        if (!empty($row['A']) && !empty($row['B'])) { // Assuming 'A' is phone number and 'B' is token

            $order_id = $row['A'];
            $receptor = $row['B'];
            $token = str_replace(' ', $row['C'], '-');

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.kavenegar.com/v1/$API_KEY/verify/lookup.json?receptor=$receptor&token=$token&token1=$order_id&template=$template",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{
                "action": "get_gateway_setting",
                "gateway_id": "golrangleasing",
                "nonce": "d5b83c4fe8"
            }',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Cookie: cookiesession1=678A8C314ABCDEFGKLMNOPQRSTUWFD42'
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            $response = json_decode($response);
            echo $response->return->status . ' - ' . $response->return->message . ' - ' . $response->entries[0]->statustext;
        }
    }
}
