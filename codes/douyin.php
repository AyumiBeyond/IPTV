<?php

$id = $_GET['id'] ?? null;
$sc = $_GET['sc'] ?? null;
if ($id == 'douyin') {
    function mk_dir($newdir)
    {
        $dir = $newdir;
        if (is_dir('./' . $dir)) {
            return $dir;
        } else {
            mkdir('./' . $dir, 0777, true);
            return $dir;
        }
    }

    mk_dir('./cache/');

    class Cache
    {
        private $cache_path;
        private $cache_expire;

        public function __construct($exp_time = 60, $path = "cache/")
        {
            $this->cache_expire = $exp_time;
            $this->cache_path = $path;
        }

        private function fileName($key)
        {
            return $this->cache_path . md5($key);
        }

        public function put($key, $data)
        {
            $values = serialize($data);
            $filename = $this->fileName($key);
            $file = fopen($filename, 'w');
            if ($file) {
                fwrite($file, $values);
                fclose($file);
            } else return false;
        }

        public function get($key)
        {
            $filename = $this->fileName($key);
            if (!file_exists($filename) || !is_readable($filename)) {
                return false;
            }
            if (time() < (filemtime($filename) + $this->cache_expire)) {
                $file = fopen($filename, "r");
                if ($file) {
                    $data = fread($file, filesize($filename));
                    fclose($file);
                    return unserialize($data);
                } else return false;
            } else return false;
        }
    }

    $cache = new Cache(60, "cache/");
    switch ($sc) {
        case 'scone':
            $playurl = $cache->get('scone_cache');
            break;
        case 'sctwo':
            $playurl = $cache->get('sctwo_cache');
            break;
        default:
            header('Location:' . 'http://159.75.85.63:5680/d/ad/playad.m3u8');
            exit();
            break;
    }
    if (!$playurl) {
        $matchurl = 'https://live.douyin.com/aweme/v1/web/backpack/match/list/?aid=6383&device_platform=web';
        $matchcontent = file_get_contents($matchurl);
        $arr = json_decode($matchcontent, true);
        $livearr = array();
        foreach ($arr["match_list"] as $value) {
            if ($value["room_status"] == 2) {
                array_push($livearr, $value["match_id"]);
            }
        };
        $first_id = $livearr[0] ?? null;
        $second_id = $livearr[1] ?? null;
        function get_url($cache, $sc, $liveid)
        {
            if ($liveid == null) {
                $adurl = 'http://159.75.85.63:5680/d/ad/playad.m3u8';
                $cache->put($sc . '_cache', $adurl);
                header("Location: $adurl");
                exit();
            }
            $liveurl = 'https://live.douyin.com/fifaworldcup/' . $liveid;
            $dyreferer = "https://live.douyin.com/$liveid";
            $cookietext = './' . mk_dir('cookies/') . md5(microtime() + $liveid) . '.' . 'txt';
            $header = array(
                'upgrade-insecure-requests: 1',
                'user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Safari/537.36',
            );
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $liveurl);
            curl_setopt($ch, CURLOPT_REFERER, $dyreferer);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_HEADER, TRUE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookietext);
            $mcontent = curl_exec($ch);
            curl_close($ch);
            preg_match('/Set-Cookie:(.*);/iU', $mcontent, $str);
            $realstr = $str[1];
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $liveurl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_REFERER, $dyreferer);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_HEADER, FALSE);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $realstr);
            $data = curl_exec($ch);
            curl_close($ch);
            $realdata = urldecode($data);
            unlink($cookietext);
            $reg = "/\"roomid\"\:\"[0-9]+\"/i";
            preg_match($reg, $realdata, $roomid);
            $nreg = "/[0-9]+/";
            preg_match($nreg, $roomid[0], $realid);
            $mediaurl = "https://live.douyin.com/webcast/room/info_by_scene/?aid=6383&live_id=1&device_platform=web&language=zh-CN&enter_from=web_search&cookie_enabled=true&screen_width=1920&screen_height=1080&browser_language=zh-CN&browser_name=Chrome&room_id=$realid[0]&scene=pc_stream_4k";
            $flvcontent = file_get_contents($mediaurl);
            $narr = json_decode($flvcontent, true);
            $playarr = $narr["data"]["stream_url"]["live_core_sdk_data"]["pull_data"]["Flv"];
            foreach ($playarr as $uhdvalue) {
                if (in_array("uhd", $uhdvalue)) {
                    $playurl = $uhdvalue["url"];
                }
            };
            return $playurl;
        }

        switch ($sc) {
            case 'scone':
                $playurl = get_url($cache, $sc, $first_id);
                $cache->put('scone_cache', $playurl);
                break;
            case 'sctwo':
                $playurl = get_url($cache, $sc, $second_id);
                $cache->put('sctwo_cache', $playurl);
                break;
            default:
                exit();
                break;
        }
    }
    header("Location: $playurl");
    exit();
} else {
    $arr = array('msg' => "failed", 'data' => "wrong value");
    echo json_encode($arr, 320);
    exit();
}