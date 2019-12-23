<?php
/*
 Update in automatic all records for Cloudflare when public IP change.
 Author: Bernardo Fuentes
 Date: 22/12/2019
*/
$current_ip = exec('sh ip.sh'); // this file get Public IP in server Linux
$config                              = array();
$config['token']                     = "token generate for cloudflare";
$config['email']                     = "account email";
$config['debug']                     = false;
$config['base_url']                  = "https://api.cloudflare.com/client/v4/";
$config['change_record_type_a']      = true;
$config['change_record_type_mx_txt'] = false;
extract($config);
function curlGet($url,$email,$token,$method,$data)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, ''.$method.'');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'X-Auth-Email: '.$email.'',
        'X-Auth-Key: '.$token.'',
        'Cache-Control: no-cache',
        'Content-Type: application/json'
    ));
    if ($method=="PUT") {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    $return = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }
    curl_close ($ch);
    return $return;
}
// GET ZONES DOMAINS
$url     = $base_url."zones/";
$domains = curlGet($url,$email,$token,"GET",null);
$domains = json_decode($domains,true);
$domains = $domains['result'];
foreach ($domains as $key => $value) {
    $id_zone     = $value['id'];
    $url_dns     = $url.$id_zone."/dns_records";
    $dns_records = json_decode(curlGet($url_dns,$email,$token,"GET",null),true);
    $dns_records = $dns_records['result'];
    if ($debug == true) {echo "Zone ID: $id_zone <br> Starting curl for $url_dns <br>";}

    foreach ($dns_records as $key => $value) {
        if ($value['type'] == "A") {
            $ip_record = $value['content'];
            $id_record = $value['id'];
            $url_update_record = $url_dns."/".$id_record;
            if ($ip_record !== $current_ip) {
                $data            = array();
                $data['type']    = $value['type'];
                $data['name']    = $value['name'];
                $data['content'] = $current_ip;
                $data['ttl']     = 1;
                $data['priority']= 10;
                $data['proxied'] = true;
                $data = json_encode($data);
                if ($debug == true) {echo "Data send: $data";}
                $update = curlGet($url_update_record,$email,$token,"PUT",$data);
                if ($debug == true) {echo "Return: $update";}
            }
        }
    }

}
