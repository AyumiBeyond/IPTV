<?php

//需要在ROOT的安卓手机安装抓包工具（抓包精灵/小黄鸟），并安装新版谷豆（腾讯应用宝下载），然后登录自己的手机号码，开启抓包后筛选关键词aut002，然后puser是你的手机号，你需要记录ptoken和pserialnumber，注意一下cid，这个数值很重要，如果cid变了你也要变，本源码只适合安卓版本，ios需要自己推几个参数！！

error_reporting(0);
header('Content-Type:text/html;charset=UTF-8');

$id = $_GET['id'];
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

    public function __construct($exp_time = 3600, $path = "cache/")
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

$cache = new Cache(3600, "cache/");


$playurl = $cache->get($id . '_cache');

if (!$playurl) {
    $user = '你的手机号';
    $ptoken = '你的ptoken';
    $pserialnumber = '你的pserialnumber';
    $cid = '你的cid值';
    $timestamp = time();
    $nonce = rand(1000000000, 9999999999);
    $str = 'sumasalt-app-portalpVW4U*FlS' . $timestamp . $nonce . $user;
    $hmac = substr(sha1($str), 0, 10);
    $onlineip = $_SERVER['REMOTE_ADDR'];
    $info = 'ptype=1&plocation=001&puser=' . $user . '&ptoken=' . $ptoken . '&pversion=030107&pserverAddress=portal.gcable.cn&pserialNumber=' . $pserialnumber . '&pkv=1&ptn=Y29tLnN1bWF2aXNpb24uc2FucGluZy5ndWRvdQ&DRMtoken=&epgID=&authType=0&secondAuthid=&t=' . $ptoken . '&pid=&cid=' . $cid . '&u=' . $user . '&p=1&l=001&d=' . $pserialnumber . '&n=' . $id . '&v=2&ot=0&pappName=GoodTV&hmac=' . $hmac . '&timestamp=' . $timestamp . '&nonce=' . $nonce;
    $url = 'http://portal.gcable.cn:8080/PortalServer-App/new/aaa_aut_aut002';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $info);
    curl_setopt($ch, CURLOPT_USERAGENT, "Apache-HttpClient/UNAVAILABLE (java 1.4)");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-FORWARDED-FOR:' . $onlineip, 'CLIENT-IP:' . $onlineip));
    $res = curl_exec($ch);
    curl_close($ch);
    $uas = parse_url($res);
    parse_str($uas["query"], $newres);
    $token = "?t=" . $newres["t"] . "&u=" . $newres["u"] . "&p=" . $newres["p"] . "&pid=&cid=" . $newres["cid"] . "&d=" . $newres["d"] . "&sid=" . $newres["sid"] . "&r=" . $newres["r"] . "&e=" . $newres["e"] . "&nc=" . $newres["nc"] . "&a=" . $newres["a"] . "&v=" . $newres["v"];
    $playurl = "http://gslb.gcable.cn:8070/live/" . $id . ".m3u8" . $token;
    $cache->put($id . '_cache', $playurl);
}
header('Location: ' . $playurl);