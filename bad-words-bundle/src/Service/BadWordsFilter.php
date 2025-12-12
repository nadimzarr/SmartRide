<?php
namespace Vendor\BadWordsBundle\Service;

class BadWordsFilter
{
    private array $badWords = [];

    public function __construct()
    {
        $file = __DIR__ . '/../Resources/badwords.txt';
        $words = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $this->badWords = array_map('trim', $words);
    }

    public function containsBadWords(string $text): bool
    {
        foreach ($this->badWords as $word) {
            if (stripos($text, $word) !== false) {
                return true;
            }
        }
        return false;
    }
    public function getBadWords(): array
{
    return $this->badWords;
}

}

