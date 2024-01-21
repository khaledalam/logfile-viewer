<?php

include_once 'MemoryHelper.php';


class FileHandler
{
    public const    LAST_INDEX = 0;
    public const    DEFAULT_PAGE_SIZE = 10;

    private ?string $inputFilePath;
    private ?int    $inputLineStartIndex_1;     // offset
    private int     $inputLinesCount;           // page size

    private array   $responseData;

    private string  $debugMemoryBefore;
    private string  $debugPeekBefore;
    private string  $debugMemoryAfter;
    private string  $debugPeekAfter;
    private float   $debugMemoryDif;
    private float   $debugPeekDif;

    public function init(): void
    {
        $this->handleGetRequestInputs();
        $this->handleInputsValidation();


        $memoryHelper = new MemoryHelper();
        [$this->debugMemoryBefore, $this->debugPeekBefore] = $memoryHelper->getMemory();
        [$debugMemoryDifB, $debugPeekDifB] = $memoryHelper->getMemory(true);


//        $a = []; // TEST Memory fail: >= 3 MB
//        foreach (range(0, 100000) as $x)$a[] = $x;


        $this->process();


        $memoryHelper = new MemoryHelper();
        [$this->debugMemoryAfter, $this->debugPeekAfter] = $memoryHelper->getMemory();
        [$debugMemoryDifA, $debugPeekDifA] = $memoryHelper->getMemory(true);

        [$this->debugMemoryDif, $this->debugPeekDif] = [abs($debugMemoryDifA - $debugMemoryDifB), abs($debugPeekDifB - $debugPeekDifA)];


        $this->sendResponse();
    }

    private function handleGetRequestInputs(): void
    {
        $this->inputFilePath            = $_GET['filePath'] ?? null;
        $this->inputLineStartIndex_1    = $_GET['lineStartIndex_1'] ?? null; // 1-based
        $this->inputLinesCount          = (int)($_GET['linesCount'] ?? self::DEFAULT_PAGE_SIZE);

        if ($this->inputLineStartIndex_1 !== null) {
            $this->inputLineStartIndex_1 = (int)$this->inputLineStartIndex_1;
        }
    }

    private function handleInputsValidation(): void
    {
        if (!$this->inputFilePath || !file_exists($this->inputFilePath)) {
            throw new \RuntimeException('File does not exist.');
        }

        if (!is_readable($this->inputFilePath)) {
            throw new \RuntimeException('File is not readable.');
        }

        if ($this->inputLineStartIndex_1 === null) {
            throw new \RuntimeException('inputLineStartIndex_1 param is missing.');
        }

        if ($this->inputLineStartIndex_1 < 1 && $this->inputLineStartIndex_1 !== self::LAST_INDEX) {
            $lastIdx = self::LAST_INDEX;
            throw new \RuntimeException("Start index must equal $lastIdx or >= 1.");
        }

        if ($this->inputLinesCount < 1) {
            throw new \RuntimeException("Count lines must be greater than or equal to 1.");
        }

        // @TODO: more rules to be added here..
    }

    private function process(): void
    {
        // File Total Lines Count ------
        $outputCountLines = NULL;
        $commandLinesCount = "wc -l < $this->inputFilePath";
        exec($commandLinesCount, $outputCountLines);
        $wcLinesCount = (int)(reset($outputCountLines));

        if ($this->inputLineStartIndex_1 === self::LAST_INDEX) {
            // Last $linesCount lines ------
            // > correct start index value instead of self::LAST_INDEX}

            $this->inputLineStartIndex_1 = max(1, $wcLinesCount - $this->inputLinesCount);
            $commandGetLines = "tail -$this->inputLinesCount $this->inputFilePath";
        } else {
            // Fetch lines [offset : offset+page_size] ------

            // /var/log/system.log
            $commandGetLines = "tail -n+$this->inputLineStartIndex_1 $this->inputFilePath | head -n$this->inputLinesCount";
        }

        $outputGetLines = [];

        exec($commandGetLines, $outputGetLines);

        $this->responseData = [
            'lines' => $outputGetLines,
            'filePath' => $this->inputFilePath,
            'lineStartIndex_1' => $this->inputLineStartIndex_1,
            'linesCount' => $this->inputLinesCount,
            'totalFileLineCount' => $wcLinesCount,
        ];
    }

    public function sendResponse(): void
    {
        $debug = ($_GET['_debugger'] ?? false) ? [ '_debugger' => [
            'processBefore' => [
                'memoryBefore' => $this->debugMemoryBefore,
                'peekBefore' => $this->debugPeekBefore,
            ],
            'processAfter' => [
                'memoryAfter' => $this->debugMemoryAfter,
                'peekAfter' => $this->debugPeekAfter,
            ],
            'processDiff' => [
                'memory' => MemoryHelper::formatBytes($this->debugMemoryDif),
                'peek' => MemoryHelper::formatBytes($this->debugPeekDif),
            ]
        ]] : [];

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array_merge($debug, $this->responseData));
    }
}
