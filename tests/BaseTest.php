<?php

namespace Matecat\XliffParser\Tests;

use Matecat\XliffParser\XliffParser;
use Matecat\XliffParser\XliffUtils\XmlParser;
use PHPUnit\Framework\TestCase;

abstract class BaseTest extends TestCase
{
    /**
     * @param string $file
     *
     * @return false|string
     */
    protected function getTestFile($file)
    {
        return file_get_contents(__DIR__ .'/files/'.$file);
    }

    /**
     * @param $file
     *
     * @return \DOMDocument
     * @throws \Matecat\XliffParser\Exception\InvalidXmlException
     * @throws \Matecat\XliffParser\Exception\XmlParsingException
     */
    protected function getTestFileAsDOMElement($file)
    {
        return XmlParser::parse(file_get_contents(__DIR__ .'/files/'.$file));
    }

    /**
     * @param string $file
     * @param array $expected
     */
    protected function assertXliffEquals($file, array $expected = [])
    {
        $parser = new XliffParser();

        $this->assertEquals($expected, $parser->xliffToArray($this->getTestFile($file)));
    }

    /**
     * Asserts that two associative arrays are similar.
     *
     * Both arrays must have the same indexes with identical values
     * without respect to key ordering
     *
     * @param array $expected
     * @param array $array
     */
    protected function assertArraySimilar(array $expected, array $array)
    {
        $this->assertTrue(count(array_diff_key($array, $expected)) === 0);

        foreach ($expected as $key => $value) {
            if (is_array($value)) {
                $this->assertArraySimilar($value, $array[$key]);
            } else {
                $this->assertStringContainsString(trim($value), trim($array[$key]));
            }
        }
    }

    /**
     * @param $data
     *
     * @return array
     */
    protected function getTransUnitsForReplacementTest($data)
    {
        $transUnits = [];

        foreach ($data as $i => $k) {
            //create a secondary indexing mechanism on segments' array; this will be useful
            //prepend a string so non-trans unit id ( ex: numerical ) are not overwritten
            $internalId = $k[ 'internal_id' ];

            $transUnits[ $internalId ] [] = $i;

            $data[ 'matecat|' . $internalId ] [] = $i;
        }

        return $transUnits;
    }

    /**
     * @param $data
     *
     * @return array
     */
    protected function getData($data)
    {
        $transUnits = [];

        foreach ($data as $i => $k) {
            //create a secondary indexing mechanism on segments' array; this will be useful
            //prepend a string so non-trans unit id ( ex: numerical ) are not overwritten
            $internalId = $k[ 'internal_id' ];

            $transUnits[ $internalId ] [] = $i;

            $data[ 'matecat|' . $internalId ] [] = $i;
        }

        return [
                'data' => $data,
                'transUnits' => $transUnits,
        ];
    }

    /**
     * @param string $url
     * @param array  $data
     *
     * @return bool|string
     */
    protected function httpPost($url, $data)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $body = curl_exec($ch);
        $error = curl_error($ch);
        $errorNo = curl_errno($ch);
        $info = curl_getinfo($ch);

        curl_close($ch);

        $http = new \stdClass();
        $http->body = $body;
        $http->error = $error;
        $http->errorNo = $errorNo;
        $http->info = $info;

        return $http;
    }

    /**
     * @param $xliff20
     *
     * @return array
     * @throws \Exception
     */
    protected function validateXliff20($xliff20)
    {
        $errors = [];

        $url = 'https://okapi-lynx.appspot.com/validation';

        $response = $this->httpPost($url, [
            'content' => $xliff20
        ]);

        if($response->info['http_code'] !== 200){
            throw new \Exception( ($response->errorNo > 0) ? $response->error : 'An error occurred calling ' . $url . '. Status code '.$response->info['http_code'].' was returned' );
        }

        preg_match_all('/<pre>(.*?)<\/pre>/s', $response->body, $matches);

        if(!empty($matches[1])){
            foreach ($matches[1] as $match){
                $errors[] = $match;
            }
        }

        return $errors;
    }
}
