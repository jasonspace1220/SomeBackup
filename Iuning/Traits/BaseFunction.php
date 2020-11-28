<?php

namespace App\Traits;

use Illuminate\Http\File;
// use File;
use Storage;

trait BaseFunction
{
    /**
     * 解密用
     *
     * @param  mixed $content
     * @return void
     */
    public function tokenDecode($content)
    {
        if (!empty($content)) {
            return openssl_decrypt(
                str_replace(" ", "+", $content),
                "AES-256-CBC",
                env('EN_TOKEN'),
                0,
                env('EN_IV')
            );
        }
    }

    /**
     * 加密用
     *
     * @param  mixed $content
     * @return void
     */
    public function tokenEncode($content)
    {
        return openssl_encrypt(
            $content,
            "AES-256-CBC",
            env('EN_TOKEN'),
            0,
            env('EN_IV')
        );
    }

    /**
     * runCurl 打CURL
     *
     * @param  mixed $paramsArray
     * @param  mixed $url
     * @param  mixed $type
     * @return void
     */
    public function runCurl($paramsArray, $url, $type = 'GET')
    {
        $ch = curl_init();
        if ($type == "GET") {
            $data = http_build_query($paramsArray);
            $getUrl = $url . "?" . $data;
            curl_setopt($ch, CURLOPT_URL, $getUrl);
        } else {
            $data = http_build_query($paramsArray);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $res = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $res;
    }

    public function runSmsCurl($url, $data)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/x-www-form-urlencoded"
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }

    /**
     * 路徑格式化(目前只有將頭尾的/去掉而已)
     *
     * @param  mixed $path
     * @return void
     */
    public function pathFormat($path)
    {
        return preg_replace('/^\/|\/$/', '', $path);
    }

    /**
     *  基本儲存檔案用
     *
     * @param  mixed $path 檔案儲存路徑
     * @param  mixed $name  檔案名稱
     * @param  mixed $file  檔案內容(Request->File)
     * @return 儲存後的路徑 (不含Domain)
     */
    public function baseStoreFile($file, $path, $name = false)
    {
        $path = $this->pathFormat($path);
        if (!$name) {
            $name = $file->getClientOriginalName();
        }
        $name = $this->pathFormat($name);
        Storage::disk('public_uploads')->putFileAs($path, new File($file), $name);
        $FilePath = 'uploads/' . $path . '/' . $name;
        return $FilePath;
    }

    /**
     * 自動儲存request中的所有檔案，
     * 並回傳檔案路徑(array)
     *
     * @param  mixed $req
     * @param  mixed $path
     * @param  mixed $name
     * @return array filePath array
     */
    public function autoStoreRequestFile($req, $path, $name = false)
    {
        $ar = [];
        $files = $req->file();
        foreach ($files as $k => $file) {
            $publicPath = $this->baseStoreFile($req->file($k), $path, $name);
            array_push($ar, $publicPath);
        }
        return $ar;
    }
}
