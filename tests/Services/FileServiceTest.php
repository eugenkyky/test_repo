<?php

namespace Tests;
//use Services
use GuzzleHttp;
use Symfony\Component\Filesystem\Filesystem;


class FileServiceTest extends \PHPUnit_Framework_TestCase
{

    const SERVER_BASE_ADDRESS = 'http://127.0.0.1:80'; // Это надо не сюда
    protected $client;

    protected function setUp()
    {
        $this->client = new GuzzleHttp\Client([
            //'base_uri' => 'http://127.0.0.1/xsolla/web/' //TODO правильная обработка
        ]);
    }

    public function testFilesList()
    {
        $response = $this->client->get( self::SERVER_BASE_ADDRESS.'/files',[ //TODO здесь не плохо бы по именам маршрутов функции. Это было бы правильно
            'query' => [
                'apikey' => 'apikey'
            ]
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        //$first_element = $data[0]; //из того предположения, что будет возвращаться хоть один элемент
        // или нет как правильно делать-то?
        $this->assertArrayHasKey('results', $data);
        //$this->assert1ArrayHasKey('result', $data);
        if ( $data['results'] > 0) {
            //$this->assertArrayHasKey('name', $data['files'][0]);
            //$this->assertArrayHasKey('name', $data['files'][0]); upload time
        }
    }

    public function testauthentication(){
        //TODO без apikey
        //TODO с неправильным apikey
    }

    public function testCreateFile()
    {
        //TODO Если существует - надо ругаться
        $body = fopen(__DIR__.'/testfile.txt', 'r');
        //Создаю на сервере
        $response = $this->client->request('POST',  self::SERVER_BASE_ADDRESS.'/files/testfile.txt?apikey=apikey123123', ['body' => $body]);
        $this->assertEquals(201, $response->getStatusCode());
        //Заберем файл с сервера и проверим его.
        $response = $this->client->request('GET',   self::SERVER_BASE_ADDRESS.'/files/testfile.txt?apikey=apikey3123123');
        $this->assertEquals(200, $response->getStatusCode());
        //Сохраним файл, посмотрим, что там есть
        $fs = new Filesystem();
        $fs->dumpFile(__DIR__.'\downloaded_file.txt', $response->getBody());
        //Читаем файл
        //Проверяем
        $this->assertStringEqualsFile(__DIR__.'\downloaded_file.txt','Test was passed successfully!');
        //Удаляем
        unlink(__DIR__.'\downloaded_file.txt');
    }


    public function testCreateFileGzipRequest(){

        //$body = fopen(__DIR__.'\testfile.txt', 'r');
        $file_string_content = file_get_contents(__DIR__.'/testfile.txt'); //читает в строку
        $compressed = gzencode($file_string_content);
        //Создаю на сервере
        $response = $this->client->request('POST',  self::SERVER_BASE_ADDRESS.'/files/gzip_testfile.txt?apikey=apikey&file_encode=gzip', ['body' => $compressed]);
        $this->assertEquals(201, $response->getStatusCode());
        //Заберем файл с сервера и проверим его.
        $response = $this->client->request('GET',  self::SERVER_BASE_ADDRESS.'/files/gzip_testfile.txt?apikey=apikey');
        $this->assertEquals(200, $response->getStatusCode());
        //Сохраним файл, посмотрим, что там есть
        $fs = new Filesystem();
        $fs->dumpFile(__DIR__.'\downloaded_file.txt', $response->getBody());
        //Читаем файл
        //Проверяем
        $this->assertStringEqualsFile(__DIR__.'\downloaded_file.txt','Test was passed successfully!');
        //Удаляем
        unlink(__DIR__.'\downloaded_file.txt');
        //TODO Закрыть fopen
    }


    /**
     * @depends testCreateFile
     */
    public function testUpdateFile() {
        $body = fopen(__DIR__.'\testfile_update.txt', 'r');
        //Создаю на сервере
        $response = $this->client->request('PUT',  self::SERVER_BASE_ADDRESS.'/files/testfile.txt?apikey=apikey', ['body' => $body]);
        $this->assertEquals(200, $response->getStatusCode());
        //Заберем файл с сервера и проверим его.
        $response = $this->client->request('GET',  self::SERVER_BASE_ADDRESS.'/files/testfile.txt?apikey=apikey');
        $this->assertEquals(200, $response->getStatusCode());
        //Сохраним файл, посмотрим, что там есть
        $fs = new Filesystem();
        $fs->dumpFile(__DIR__.'\downloaded_file.txt', $response->getBody());
        //Читаем файл
        //Проверяем
        $this->assertStringEqualsFile(__DIR__.'\downloaded_file.txt','Update Test was passed successfully!');
        //Удаляем
        unlink(__DIR__.'\downloaded_file.txt');
    }

    /**
     * @depends testCreateFile
     */
    public function testGetFileMeta(){
        //{"size":36,"modified":"May 13 2016 12:04:02.","filename":"testfile.txt","md5":"3c953a9d0c21610cb07018e3d2e58038","mime_type":"text\/plain"}
        $response = $this->client->get(  self::SERVER_BASE_ADDRESS.'/files/testfile.txt/meta',[ //TODO здесь не плохо бы по именам маршрутов функции. Это было бы правильно
            'query' => [
                'apikey' => 'apikey'
            ]
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('size', $data);
        $this->assertArrayHasKey('modified', $data);
        $this->assertArrayHasKey('filename', $data);
        $this->assertArrayHasKey('md5', $data);
        $this->assertArrayHasKey('mime_type', $data);
    }
}
