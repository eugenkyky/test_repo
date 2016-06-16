<?php

namespace Services;

use Silex\Application;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Security\Acl\Exception\Exception;
use Symfony\Component\HttpFoundation\{Request,Response};
use Symfony\Component\Filesystem\{Exception\IOException, Filesystem};

class FileService
{
    public static function dirSize(string $directory): int
    {
        $size = 0;
        foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory)) as $file){
            $size += $file->getSize();
        }
        return $size;
    }

    public static function getCurrentUserDir(Application $app): string
    {
        $token = $app['security.token_storage']->getToken();
        if (null !== $token) {
            $user = $token->getUser();
        } else {
            throw new Exception();
        }
        return $user->getUsername();
    }

    public static function makeFilePath(string $filename, Application $app): string
    {
        $users_dir = self::getCurrentUserDir($app);
        return $app['store_dir'].$users_dir.'/'.$filename;
    }

    public static function readFileMIME(string $path): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $type = finfo_file($finfo, $path);
        return $type;
    }

    public static function readFileMeta(string $path): array
    {
        $meta = array();
        $meta['size'] = filesize($path);
        $meta['modified'] = date ("F d Y H:i:s.", filemtime($path));
        $meta['md5'] = md5_file($path);
        $meta['mime_type'] = self::readFileMIME($path);
        return $meta;
    }

    public function filesList(Application $app): Response
    {
        try {
            $files = scandir($app['store_dir'] . self::getCurrentUserDir($app));
            if (FALSE == $files) {
                //TODO log
                return new Response($app[500], 500);
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
                    return new Response($app[500], 500);
                } else {
                    return new Response($answer, 200);
                }
            }
        } catch (Exception $e) {
            //TODO log, stat
            return new Response($app[500], 500);
        } catch (\Error $e){
            //TODO log, stat
            return new Response($app[500], 500);
        } finally{
            //TODO log, stat
        }
    }

    public function createFile(Request $request, Application $app, string $filename): Response
    {
        try {
            $file_path = self::makeFilePath($filename, $app);
            if (!file_exists($file_path)) {
                $users_dir = self::getCurrentUserDir($app);
                $content = $request->getContent();
                $current_consumed_place = $this->dirSize($app['store_dir'] . $users_dir);
                $encoding = $request->query->get('file_encode');
                if ($encoding == null) {
                    //pass
                } elseif ($encoding == 'gzip') {
                    $content = gzdecode($content);
                    if (FALSE == $content) {
                        //TODO log
                        return new Response($app[500], 500);
                    }
                } else {
                    //TODO log
                    return new Response($app[400], 400);
                }
                $attempting_consumed_place = $current_consumed_place + strlen($content);
                if ($attempting_consumed_place > $app['quota']) {
                    return new Response('Disk usage limit exceeded', 409);
                } else {
                    $fs = new Filesystem();
                    $fs->dumpFile($file_path, $content);
                    if (file_exists($file_path)) {
                        $meta = self::readFileMeta($file_path);
                        $meta['filename'] = $filename;
                        $answer = json_encode($meta);
                        return new Response($answer, 201);
                    } else {
                        //TODO log
                        return new Response($app[500], 500);
                    }
                }
            } else {
                return new Response('File already exist', 409);
            }
        } catch (LogicException $exception) {
            //TODO log $exception
            return new Response($app[400], 400);
        } catch (IOException $exception) {
            //TODO log $exception
            return new Response($app[400], 500);
        } catch (Exception $exception) {
            //TODO log $exception
            return new Response($app[500], 500);
        } catch (\Error $e) {
            //TODO log eroor
            return new Response($app[500], 500);
        } finally {
            //TODO log, add to stat
        }
    }

    public function updateFile(Request $request, Application $app, string $filename): Response
    {
        try {
            $file_path = self::makeFilePath($filename , $app);
            if (file_exists($file_path)) {
                $content = $request->getContent();
                $users_dir = self::getCurrentUserDir($app);
                $current_consumed_place = self::dirSize($app['store_dir'] . $users_dir);
                $current_file_size = filesize($file_path); //todo 32 bit OS

                $encoding = $request->query->get('file_encode');
                if ($encoding == null) {
                    //pass
                } elseif ($encoding == 'gzip') {
                    $content = gzdecode($content);
                    if (FALSE == $content) {
                        //TODO log
                        return new Response($app[500], 500);
                    }
                } else {
                    //TODO log
                    return new Response($app[400], 400);
                }

                $space_without_file = $current_consumed_place - $current_file_size;
                $potential_disk_space = $space_without_file + strlen($content);
                if ($potential_disk_space > $app['quota']) {
                    return new Response('Disk limit has been exceeded', 409);
                } else {
                    $fs = new Filesystem();
                    $fs->dumpFile($file_path, $content);
                    $meta = self::readFileMeta($file_path);
                    $meta['filename'] = $filename;
                    $answer = json_encode($meta);
                    return new Response($answer, 200);
                }

            } else {
                return new Response($app[404], 404);
            }
        } catch (LogicException $exception) {
            //TODO log $exception
            return new Response($app[400], 400);
        } catch (IOException $exception){
            //TODO log $exception
            return new Response($app[500], 500);
        } catch (Exception $e) {
            //TODO logexception
            return new Response($app[500], 500);
        } catch (\Error $e){
            //TODO log eroor
            return new Response(500, 500);
        } finally{
            //TODO log, add to stat
        }
    }

    public function getFileContent(Application $app, string $filename): Response
    {
        try {
            $file_path = self::makeFilePath($filename, $app);
            if (file_exists($file_path)) {
                $bytes = readfile($file_path);
                if (FALSE == $bytes) {
                    return new Response($app[500], 500);
                } else {
                    /*
                    //http_response_code(201);
                    header('Content-Description: File Transfer');
                    header('Content-Type: ' . self::readFileMIME($file_path));
                    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate');
                    header('Pragma: public');
                    header('Content-Length: ' . filesize($file_path));
                    exit; //TODO что делает?
                    */
                    // Generate response
                    $response = new Response();
                    $response->headers->set('Content-type', self::readFileMIME($file_path));
                    $response->headers->set('Content-Disposition', 'attachment; filename="' . basename($filename) . '";');
                    $response->headers->set('Content-length', filesize($file_path));
                    $response->setStatusCode(201);
                    // Send headers before outputting anything
                    $response->setContent(file_get_contents($filename));
                    return $response;
                }
            } else {
                //TODO log
                return new Response($app[404], 404);
            }
        } catch (Exception $e){
            //TODO log exception
            return new Response($app[500], 500);
        } catch (\Error $e){
            //TODO log eroor
            return new Response($app[500], 500);
        } finally{
            //TODO log, add to stat
        }
    }

    public function getFileMeta(Application $app, string $filename): Response
    {
        try {
            $file_path = self::makeFilePath($filename, $app);
            if (file_exists($file_path)) {
                $meta = self::readFileMeta($file_path);
                $meta['filename'] = $filename;
                $answer = json_encode($meta);
                return new Response($answer, 200);
            } else {
                //TODO log
                return new Response($app[404], 404);
            }
        } catch (Exception $e) {
            //TODO log exception
            return new Response($app[500], 500);
        } catch (\Error $e){
            //TODO log eroor
            return new Response($app[500], 500);
        } finally{
            //TODO log, add to stat
        }
    }
}