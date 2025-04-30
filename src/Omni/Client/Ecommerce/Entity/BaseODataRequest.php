<?php

abstract class BaseODataRequest
{
    public ?string $storeNo = null;
    public int $batchSize;
    public bool $fullRepl;
    public string $lastKey;
    public int $lastEntryNo;

    public function __construct(array $data)
    {
        $this->storeNo = $data['storeNo'] ?? null;
        $this->batchSize = (int)($data['batchSize'] ?? 0);
        $this->fullRepl = (bool)($data['fullRepl'] ?? false);
        $this->lastKey = (string)($data['lastKey'] ?? '');
        $this->lastEntryNo = (int)($data['lastEntryNo'] ?? 0);
    }

    abstract public function getActionName(): string;
}