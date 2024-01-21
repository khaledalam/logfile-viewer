<?php

require_once 'FileHandler.php';

use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

final class LogfileViewerTest extends TestCase
{
    /** @var string */
    private static string $pid;

    private static Client $client;

    private static string $test_php_server_port = '8099';

    private array $basic_auth = [
        'username' => 'admin',
        'password' => 'admin',
    ];

    public static function setUpBeforeClass(): void
    {
        $port = self::$test_php_server_port;
        exec("pkill -9 -f localhost:$port");

        self::$pid = shell_exec(sprintf(
            '%s > %s 2>&1 & echo $!',
            "php -S localhost:$port -t .",
            '/dev/null'
        ));

        sleep(1);

        self::$client = new Client(['http_errors' => false]);
    }

    public static function tearDownAfterClass(): void
    {
        exec("kill -9 " . self::$pid);
        unlink('test.in');
    }

    /**
     * Make sure not exceed 3 MB RAM usage regardless of the file size.
     *
     * @throws GuzzleException
     */
    public function testMemoryUsage(): void
    {
        exec("seq 10000 > test.in");

        $response = self::$client->get( "http://localhost:8099", [
            'query' => [
                'auth' => $this->basic_auth,
                'filePath' => './test.in',
                'linesCount' => 10,
                'lineStartIndex_1' => 1,
                '_debugger' => 'yes'
            ]
        ]);

        $res = json_decode($response->getBody()->getContents(), true)['_debugger']['processDiff']['memory'];

        $this->assertEquals(200, $response->getStatusCode());

        $unit = trim(explode(' ', $res)[1]);
        if ($unit === 'KB' || $unit === '') {
            $this->assertTrue(true);
        } else if ($unit === 'MB') {
            $this->assertLessThanOrEqual(3, (float)explode(' ', $res)[0]);
        } else {
            $this->assertTrue(false);
        }
    }

    /**
     * Make sure missing params thraw exceptions.
     *
     * @dataProvider missingInputData
     */
    public function testMissingInputs($exampleKey, $exampleErrorMsg): void
    {
        exec("seq 100 > test.in");

        $q = [
            'auth' => $this->basic_auth,
            'filePath' => './test.in',
            'linesCount' => 10,
            'lineStartIndex_1' => 1,
            '_debugger' => 'yes'
        ];
        unset($q[$exampleKey]);

        $response = self::$client->get( "http://localhost:8099", [
            'query' => $q
        ]);

        $this->assertStringContainsString($exampleErrorMsg, $response->getBody()->getContents());
    }



    // @TODO: other test methods can be added to handle remain rules and logic...


    public function missingInputData(): array
    {
        return [
            ['filePath', 'File does not exist'],
            ['lineStartIndex_1', 'inputLineStartIndex_1 param is missing.']
            // @TODO: ... other rules
        ];
    }

}