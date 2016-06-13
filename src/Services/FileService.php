<?php
namespace Services;

use Silex\Application;

use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Security\Acl\Exception\Exception;
use Symfony\Component\HttpFoundation\{Request,Response};
use Symfony\Component\Filesystem\{Exception\IOException, Filesystem};


class FileService
{
    static $CODE_400_TEXT = 'Bad request';
    static $CODE_404_TEXT = 'File not found';
    static $CODE_500_TEXT = 'Internal Server Error';

    protected $working_dir = __DIR__.'/../../users_files/';

    //TODO прочекать функцию и посмотреить у остальных может exp может дать
    //TODO почему эта функция не статична

    /**
     * Get the directory size
     * @param string $directory
     * @return integer
     */
    protected function dirSize(string $directory): int {
        $size = 0;
        foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory)) as $file){
            $size += $file->getSize();
        }
        return $size;
    }

    /**
     * Get users dir
     * @param Application $app
     * @return string
     */
    protected function getCurrentUserDir(Application $app): string {  //TODO доступ
        $token = $app['security.token_storage']->getToken();
        if (null !== $token) {
            $user = $token->getUser();
        } else {
            throw new Exception();
        }
        return $user->getUsername();
    }

    /**
     * Get users dir
     * @param string $filename,  Application $app
     * @return string
     */
    protected function makeFilePath(string $filename, Application $app): string {
        $users_dir = $this->getCurrentUserDir($app);
        return $this->working_dir.$users_dir.'/'.$filename;
    }

    protected function readFileMIME(string $path): string {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        //$meta['mime_type'] = finfo_file($finfo, $path);
        //finfo_close($finfo);
        $type = finfo_file($finfo, $path);
        return $type;
    }

    protected function readFileMeta(string $path): array {
        $meta = array();
        $meta['size'] = filesize($path);
        $meta['modified'] = date ("F d Y H:i:s.", filemtime($path));
        $meta['md5'] = md5_file($path);
        $meta['mime_type'] = $this->readFileMIME($path);
        return $meta;
    }


    public function filesList(Application $app): Response {
        try {
            $files = scandir($this->working_dir . $this->getCurrentUserDir($app));
            if (FALSE == $files) {
                //TODO log
                return new Response(self::$CODE_500_TEXT, 500);
            } else {
                unset($files[0], $files[1]); // unset '.', '..' dirs
                $result_array = array();
                if (empty($files)) {
                    $result_array['results'] = 0;
                    $result_array['files'] = array();
                } else {
                    $result_array['results'] = sizeof($files);
                    $result_array['files'] = array_values($files);
                }
                $answer = json_encode($result_array);
                if (FALSE == $answer) {
                    //TODO log
                    return new Response(self::$CODE_500_TEXT, 500);
                } else {
                    return new Response($answer, 200);
                }
            }
        } catch (Exception $e) {
            //TODO log exception
            return new Response(self::$CODE_500_TEXT, 500);
        } catch (\Error $e){
            //TODO log eroor
            return new Response(self::$CODE_500_TEXT, 500);
        } finally{
            //TODO log, add to stat
        }
    }

    public function createFile(Request $request, Application $app, string $filename): Response{
        //TODO Проверить файл по имени
        //TODO All bytes except NUL ('\0') and '/' and '.', '..'
        //TODO 201 (Created), заголовок 'Location' ссылается на /customers/{id}, где ID - идентификатор нового экземпляра.
        //TODO Если такой файл есть - выдать ошибку
        try {
            $file_path = $this->makeFilePath($filename , $app);
            if (!file_exists($file_path)) {
                $users_dir = $this->getCurrentUserDir($app);
                $content = $request->getContent();
                $current_consumed_place = $this->dirSize($this->working_dir . $users_dir);
                //return new Response(self::$CODE_500_TEXT, 500);
                $attempting_consumed_place = $current_consumed_place + strlen($content); //TODO ошибка, что если файл закодирован
                if ($attempting_consumed_place > $app['quota']) {
                    return new Response('Disk usage limit exceeded', 409);
                } else {
                    $encoding = $request->query->get('file_encode');
                    if ($encoding == null) {
                        //Стандартная обработка
                    } elseif ($encoding == 'gzip') {
                        //меняю content
                        $content = gzdecode($content);
                        if (FALSE == $content) {
                            //TODO log
                            return new Response(self::$CODE_500_TEXT, 500);
                        }
                    } else {
                        //TODO log
                        return new Response(self::$CODE_400_TEXT, 400);
                    }

                    $fs = new Filesystem();
                    $fs->dumpFile($file_path, $content);
                    if (file_exists($file_path)) {
                        $meta = $this->readFileMeta($file_path);
                        $meta['filename'] = $filename;
                        $answer = json_encode($meta);
                        return new Response($answer, 201);
                    } else {
                        //TODO log
                        return new Response(self::$CODE_500_TEXT, 500);
                    }
                }
            } else {
                return new Response('File already exist', 409);
            }
        } catch (LogicException $exception){
            //TODO log $exception
            return new Response(self::$CODE_400_TEXT, 400);
        } catch (IOException $exception){
            //TODO log $exception
            return new Response(self::$CODE_500_TEXT, 500);
        } catch (Exception $exception) {
            //TODO log $exception
            return new Response(self::$CODE_500_TEXT, 500);
        } catch (\Error $e){
            //TODO log eroor
            return new Response(self::$CODE_500_TEXT, 500);
        } finally{
            //TODO log, add to stat
        }
    }

    public function updateFile(Request $request, Application $app, string $filename): Response {
        try {
            $file_path = $this->makeFilePath($filename , $app);
            if (file_exists($file_path)) {
                $content = $request->getContent();
                $users_dir = $this->getCurrentUserDir($app);
                $current_consumed_place = $this->dirSize($this->working_dir . $users_dir);
                $current_file_size = filesize($file_path); //todo 32 bit OS

                $encoding = $request->query->get('file_encode');
                if ($encoding == null) {
                    //Стандартная обработка
                } elseif ($encoding == 'gzip') {
                    //меняю content
                    $content = gzdecode($content);
                    if (FALSE == $content) {
                        //TODO log
                        return new Response(self::$CODE_500_TEXT, 500);
                    }
                } else {
                    //TODO log
                    return new Response(self::$CODE_400_TEXT, 400);
                }

                $space_without_file = $current_consumed_place - $current_file_size;
                $potential_disk_space = $space_without_file + strlen($content);
                if ($potential_disk_space > $app['quota']) {
                    return new Response('Disk limit has been exceeded', 409);
                } else {
                    $fs = new Filesystem();
                    $fs->dumpFile($file_path, $content);
                    $meta = $this->readFileMeta($file_path);
                    $meta['filename'] = $filename;
                    $answer = json_encode($meta);
                    return new Response($answer, 200);
                }

            } else {
                return new Response(self::$CODE_404_TEXT, 404);
            }
        } catch (LogicException $exception) {
            //TODO log $exception
            return new Response(self::$CODE_400_TEXT, 400);
        } catch (IOException $exception){
            //TODO log $exception
            return new Response(self::$CODE_500_TEXT, 500);
        } catch (Exception $e) {
            //TODO logexception
            return new Response(self::$CODE_500_TEXT, 500);
        } catch (\Error $e){
            //TODO log eroor
            return new Response(self::$CODE_500_TEXT, 500);
        } finally{
            //TODO log, add to stat
        }
    }

    public function getFileContent(Application $app, string $filename): Response {
        try {
            $file_path = $this->makeFilePath($filename, $app);
            if (file_exists($file_path)) {
                $bytes = readfile($file_path); //TODO
                if (FALSE == $bytes) {
                    return new Response(self::$CODE_500_TEXT, 500); 
                } else {
                    //http_response_code(201);
                    header('Content-Description: File Transfer');
                    //header('Content-Type: application/octet-stream'); // readFileMIME
                    header('Content-Type: '.$this->readFileMIME($file_path)); //TODO Content-type
                    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate');
                    header('Pragma: public');
                    header('Content-Length: ' . filesize($file_path));
                    exit; //TODO что делает?

                    /*
                     * // Generate response
                        $response = new Response();
                        // Set headers
                        $response->headers->set('Cache-Control', 'private');
                        $response->headers->set('Content-type', mime_content_type($filename));
                        $response->headers->set('Content-Disposition', 'attachment; filename="' . basename($filename) . '";');
                        $response->headers->set('Content-length', filesize($filename));
                        // Send headers before outputting anything
                        $response->sendHeaders();
                        $response->setContent(file_get_contents($filename));
                     */

                }
            } else {
                //TODO log
                return new Response(self::$CODE_404_TEXT, 404);
            }
        } catch (Exception $e){
            //TODO log exception
            return new Response(self::$CODE_500_TEXT, 500);
        } catch (\Error $e){
            //TODO log eroor
            return new Response(self::$CODE_500_TEXT, 500);
        } finally{
            //TODO log, add to stat
        }
    }

    public function getFileMeta(Application $app, string $filename): Response {
        try {
            $file_path = $this->makeFilePath($filename, $app);
            if (file_exists($file_path)) {
                $meta = $this->readFileMeta($file_path);
                $meta['filename'] = $filename;
                $answer = json_encode($meta);
                return new Response($answer, 200);
            } else {
                //TODO log
                return new Response(self::$CODE_404_TEXT, 404);
            }
        } catch (Exception $e) {
            //TODO log exception
            return new Response(self::$CODE_500_TEXT, 500);
        } catch (\Error $e){
            //TODO log eroor
            return new Response(self::$CODE_500_TEXT, 500);
        } finally{
            //TODO log, add to stat
        }
    }
}