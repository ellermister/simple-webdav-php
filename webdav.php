<?php
function http_code($num)
{
    $http = array(
        100 => "HTTP/1.1 100 Continue",
        101 => "HTTP/1.1 101 Switching Protocols",
        200 => "HTTP/1.1 200 OK",
        201 => "HTTP/1.1 201 Created",
        202 => "HTTP/1.1 202 Accepted",
        203 => "HTTP/1.1 203 Non-Authoritative Information",
        204 => "HTTP/1.1 204 No Content",
        205 => "HTTP/1.1 205 Reset Content",
        206 => "HTTP/1.1 206 Partial Content",
        207 => "HTTP/1.1 207 Multi-Status",
        300 => "HTTP/1.1 300 Multiple Choices",
        301 => "HTTP/1.1 301 Moved Permanently",
        302 => "HTTP/1.1 302 Found",
        303 => "HTTP/1.1 303 See Other",
        304 => "HTTP/1.1 304 Not Modified",
        305 => "HTTP/1.1 305 Use Proxy",
        307 => "HTTP/1.1 307 Temporary Redirect",
        400 => "HTTP/1.1 400 Bad Request",
        401 => "HTTP/1.1 401 Unauthorized",
        402 => "HTTP/1.1 402 Payment Required",
        403 => "HTTP/1.1 403 Forbidden",
        404 => "HTTP/1.1 404 Not Found",
        405 => "HTTP/1.1 405 Method Not Allowed",
        406 => "HTTP/1.1 406 Not Acceptable",
        407 => "HTTP/1.1 407 Proxy Authentication Required",
        408 => "HTTP/1.1 408 Request Time-out",
        409 => "HTTP/1.1 409 Conflict",
        410 => "HTTP/1.1 410 Gone",
        411 => "HTTP/1.1 411 Length Required",
        412 => "HTTP/1.1 412 Precondition Failed",
        413 => "HTTP/1.1 413 Request Entity Too Large",
        414 => "HTTP/1.1 414 Request-URI Too Large",
        415 => "HTTP/1.1 415 Unsupported Media Type",
        416 => "HTTP/1.1 416 Requested range not satisfiable",
        417 => "HTTP/1.1 417 Expectation Failed",
        500 => "HTTP/1.1 500 Internal Server Error",
        501 => "HTTP/1.1 501 Not Implemented",
        502 => "HTTP/1.1 502 Bad Gateway",
        503 => "HTTP/1.1 503 Service Unavailable",
        504 => "HTTP/1.1 504 Gateway Time-out"
    );
    return $http[$num];
}

function response_http_code($num)
{
    header(http_code($num));
}
function response_basedir($dir, $lastmod, $status)
{
    $lastmod = gmdate("D, d M Y H:i:s", $lastmod)." GMT";
    $fmt = <<<EOF
<d:response>
        <d:href>{$dir}</d:href>
        <d:propstat>
            <d:prop>
                <d:getlastmodified>{$lastmod}</d:getlastmodified>
                <d:resourcetype>
                    <d:collection/>
                </d:resourcetype>
            </d:prop>
            <d:status>{$status}</d:status>
        </d:propstat>
    </d:response>
EOF;
    // /dav/
    //Sun, 11 Apr 2021 16:23:30 GMT
    // HTTP/1.1 200 OK

    return $fmt;
}
function response_dir($dir, $lastmod, $status)
{
    $lastmod = gmdate("D, d M Y H:i:s", $lastmod)." GMT";
    $fmt = <<<EOF
  <D:response>
    <D:href>{$dir}</D:href>
    <D:propstat>
      <D:prop>
        <D:resourcetype>
          <D:collection></D:collection>
        </D:resourcetype>
        <D:getlastmodified>{$lastmod}</D:getlastmodified>
        <D:displayname/>
      </D:prop>
      <D:status>{$status}</D:status>
    </D:propstat>
  </D:response>
EOF;
    // /dav/
    //Sun, 11 Apr 2021 16:23:30 GMT
    // HTTP/1.1 200 OK

    return $fmt;
}

function response_file($file_path, $lastmod, $file_length, $status)
{
    $lastmod = gmdate("D, d M Y H:i:s", $lastmod)." GMT";
    $tag = md5($lastmod.$file_path);
    $fmt = <<<EOF
  <D:response>
    <D:href>{$file_path}</D:href>
    <D:propstat>
      <D:prop>
        <D:resourcetype/>
        <D:getcontentlength>{$file_length}</D:getcontentlength>
        <D:getetag>"{$tag}"</D:getetag>
        <D:getcontenttype/>
        <D:displayname/>
        <D:getlastmodified>{$lastmod}</D:getlastmodified>
      </D:prop>
      <D:status>{$status}</D:status>
    </D:propstat>
  </D:response>
EOF;
    // /dav/%E6%96%B0%E5%BB%BA%E6%96%87%E6%9C%AC%E6%96%87%E6%A1%A3.txt
    // 0
    // HTTP/1.1 200 OK
    // Mon, 12 Apr 2021 06:32:44 GMT
    return $fmt;

}

function response($text)
{
    return <<<EOF
<?xml version="1.0" encoding="utf-8"?>
<D:multistatus xmlns:D="DAV:">
  {$text}
</D:multistatus>
EOF;
}

class dav{

    protected  $public;

    public function __construct()
    {
        $this->public = __DIR__.'/public';
    }

    public function options()
    {
        header('Allow: OPTIONS, GET, PUT, PROPFIND, PROPPATCH');
        // Allow: OPTIONS, GET, PUT, PROPFIND, PROPPATCH, ACL
        response_http_code(200);
    }

    public function head()
    {
        header('Content-Type: application/octet-stream');

        $path = $this->public.'/'.ltrim($_SERVER['PATH_INFO'],'/');
        if(is_file($path)){
            header('Content-Length: '.filesize($path));
            $lastmod = filemtime($path);
            $lastmod = gmdate("D, d M Y H:i:s", $lastmod)." GMT";
            header('Last-Modified: '.$lastmod);
        }else{
            response_http_code(404);
        }
    }

    public function get()
    {
        header('Content-Type: application/octet-stream');
        $path = $this->public.'/'.ltrim($_SERVER['PATH_INFO'],'/');
        if(is_file($path)){
            $fh = fopen($path,'r');
            $oh = fopen('php://output','w');
            stream_copy_to_stream($fh, $oh);
            fclose($fh);
            fclose($oh);
        }else{
            response_http_code(404);
        }
    }

    public function put()
    {
        $input = fopen("php://input",'r');
        try{
            $path = $this->public.'/'.ltrim($_SERVER['PATH_INFO'],'/');
            $fh = fopen($path,'w');
            stream_copy_to_stream($input, $fh);
            fclose($fh);

        }catch (Throwable $throwable){
            response_http_code(503);
            echo $throwable->getMessage();
        }
    }


    public function propfind()
    {
        $path = $this->public.'/'.ltrim($_SERVER['PATH_INFO'] ?? '','/');
        $dav_base_dir = $_SERVER['SCRIPT_NAME']. '/'.ltrim($_SERVER['PATH_INFO'] ?? '','/');

        if(isset($_SERVER['HTTP_DEPTH'])){
            if($_SERVER['HTTP_DEPTH'] == 0){
                if(is_file($path)){
                    $response_text = response_file($dav_base_dir,filemtime($path),filesize($path),http_code(200));
                }elseif(is_dir($path)){
                    $response_text = response_basedir($dav_base_dir,filemtime($path),http_code(200));
                }else{
                    response_http_code(404);
                    return;
                }
                response_http_code(207);
                header('Content-Type: text/xml; charset=utf-8');
                echo response($response_text);
                exit;
            }
        }

        $files = scandir($path);

        $response_text = response_basedir($dav_base_dir,filemtime($path),http_code(200));
        foreach ($files as $file){
            if($file == '.' || $file == '..'){
                continue;
            }
            $file_path  = $path.'/'.$file;
            $mtime = filemtime($file_path);

            if(is_dir($file_path)){
                $response_text.= response_dir($dav_base_dir.'/'.$file,$mtime,http_code(200));
            }elseif(is_file($file_path)){
                $response_text.= response_file($dav_base_dir.'/'.$file, $mtime,filesize($file_path),http_code(200));
            }
        }
        response_http_code(207);
        header('Content-Type: text/xml; charset=utf-8');
        echo response($response_text);
    }

    public function delete()
    {
        header('Content-Type: application/octet-stream');
        $path = $this->public.'/'.ltrim($_SERVER['PATH_INFO'],'/');
        if($path){
            if(unlink($path)){
                response_http_code(200);
            }else{
                response_http_code(503);
            }
        }else{
            response_http_code(404);
        }
    }

    public function lock()
    {
        response_http_code(501);
    }

    public function proppatch()
    {
        $path = $this->public.'/'.ltrim($_SERVER['PATH_INFO'],'/');
        echo <<<EOF
<?xml version="1.0" encoding="utf-8" ?> 
<D:multistatus xmlns:D="DAV:">  
  <D:response>  
    <D:href>{$path}</D:href> 
    <D:propstat> 
      <D:prop><D:owner/></D:prop> 
      <D:status>HTTP/1.1 403 Forbidden</D:status> 
      <D:responsedescription>
        <D:error><D:cannot-modify-protected-property/></D:error>
        Failure to set protected property (DAV:owner) 
      </D:responsedescription> 
    </D:propstat> 
  </D:response> 
</D:multistatus> 
EOF;

        response_http_code(207);
    }

    public function mkcol()
    {
        $path = $this->public.'/'.ltrim($_SERVER['PATH_INFO'],'/');
        if(!file_exists($path)){
            mkdir($path);
            response_http_code(200);
        }else{
            response_http_code(403);
        }
    }

    public function move()
    {
        $path = $this->public.'/'.ltrim($_SERVER['PATH_INFO'],'/');
        $dest = $_SERVER['HTTP_DESTINATION']; // http://127.0.0.1:9999/webdav.php/dffds
        $pos = strpos($dest, $_SERVER['SCRIPT_NAME']);
        $dest = substr($dest,$pos + strlen($_SERVER['SCRIPT_NAME']));
        $dest = $this->public.'/'.ltrim($dest,'/');
        if(file_exists($path)){
            rename($path, $dest);
            response_http_code(200);
        }else{
            response_http_code(403);
        }
    }
}

$dav = new dav();
$request_method = strtolower($_SERVER['REQUEST_METHOD']);
$header_text = "";
foreach (getallheaders() as $name => $value) {
    $header_text.="$name: $value\n";
}
$input = file_get_contents("php://input");
file_put_contents('./HEAD.log', $request_method.' '.$_SERVER['REQUEST_URI'].PHP_EOL.$header_text. PHP_EOL.$input.PHP_EOL,FILE_APPEND);
if (method_exists($dav, $request_method)) {
    $dav->$request_method();
} else {
    // 405 Method Not Allowed
    response_http_code(405);
}
