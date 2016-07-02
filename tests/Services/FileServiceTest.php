<?php

namespace Tests;

use GuzzleHttp;
use Symfony\Component\Filesystem\Filesystem;


class FileServiceTest extends \PHPUnit_Framework_TestCase
{
    const SERVER_BASE_ADDRESS = 'http://127.0.0.1:80';
    protected $client;

    protected function setUp()
    {
        $this->client = new GuzzleHttp\Client();
    }

    public function testFilesList()
    {
        $response = $this->client->get( self::SERVER_BASE_ADDRESS.'/files',[
            'query' => [
                'apikey' => 'apikey'
            ]
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('results', $data);
        $this->assertArrayHasKey('files', $data);
    }

    public function testAuthentication(){
        try {
            $this->client->get( self::SERVER_BASE_ADDRESS.'/files');
        }
        catch (GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $this->assertEquals(403, $response->getStatusCode());
        }
    }

    public function testAuthentication2(){
        try {
            $this->client->get( self::SERVER_BASE_ADDRESS.'/files?apikey=not_existed_api_key');
        }
        catch (GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $this->assertEquals(403, $response->getStatusCode());
        }
    }



    public function testCreateFile()
    {
        $body = fopen(__DIR__.'/testfile.txt', 'r');
        //create on server
        $response = $this->client->request('POST',  self::SERVER_BASE_ADDRESS.'/files/testfile.txt?apikey=apikey', ['body' => $body]);
        $this->assertEquals(201, $response->getStatusCode());
        //download and check
        $response = $this->client->request('GET',   self::SERVER_BASE_ADDRESS.'/files/testfile.txt?apikey=apikey');
        $this->assertEquals(200, $response->getStatusCode());
        //save for check
        $fs = new Filesystem();
        $fs->dumpFile(__DIR__.'\downloaded_file.txt', $response->getBody());
        $this->assertStringEqualsFile(__DIR__.'\downloaded_file.txt','Test was passed successfully!');
        unlink(__DIR__.'\downloaded_file.txt');
    }
    
    /**
     * @depends testCreateFile
     */
    public function testCreateExistingFile()
    {
        try {
            $body = fopen(__DIR__.'/testfile.txt', 'r');
            //create on server
            $this->client->request('POST',  self::SERVER_BASE_ADDRESS.'/files/testfile.txt?apikey=apikey', ['body' => $body]);
        }
        catch (GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $this->assertEquals(409, $response->getStatusCode());
        }
    }


    public function testCreateFileGzipRequest(){

        $file_string_content = file_get_contents(__DIR__.'/testfile.txt');
        //encode file
        $compressed = gzencode($file_string_content);
        //create on server
        $response = $this->client->request('POST',  self::SERVER_BASE_ADDRESS.'/files/gzip_testfile.txt?apikey=apikey&file_encode=gzip', ['body' => $compressed]);
        $this->assertEquals(201, $response->getStatusCode());
        //download and check
        $response = $this->client->request('GET',  self::SERVER_BASE_ADDRESS.'/files/gzip_testfile.txt?apikey=apikey');
        $this->assertEquals(200, $response->getStatusCode());
        //save
        $fs = new Filesystem();
        $fs->dumpFile(__DIR__.'\downloaded_file.txt', $response->getBody());
        $this->assertStringEqualsFile(__DIR__.'\downloaded_file.txt','Test was passed successfully!');
        unlink(__DIR__.'\downloaded_file.txt');
    }

    /**
     * @depends testCreateFile
     */
    public function testUpdateFile() {
        $body = fopen(__DIR__.'/testfile_update.txt', 'r');
        $response = $this->client->request('PUT',  self::SERVER_BASE_ADDRESS.'/files/testfile.txt?apikey=apikey', ['body' => $body]);
        $this->assertEquals(200, $response->getStatusCode());
        $response = $this->client->request('GET',  self::SERVER_BASE_ADDRESS.'/files/testfile.txt?apikey=apikey');
        $this->assertEquals(200, $response->getStatusCode());
        $fs = new Filesystem();
        $fs->dumpFile(__DIR__.'\downloaded_file.txt', $response->getBody());
        $this->assertStringEqualsFile(__DIR__.'\downloaded_file.txt','Update Test was passed successfully!');
        unlink(__DIR__.'\downloaded_file.txt');
    }

    /**
     * @depends testCreateFile
     */
    public function testGetFileMeta(){
        $response = $this->client->get(  self::SERVER_BASE_ADDRESS.'/files/testfile.txt/meta',[
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
