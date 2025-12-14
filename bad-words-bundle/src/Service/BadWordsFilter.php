<?php
namespace Vendor\BadWordsBundle\Service;

class BadWordsFilter
{
    private array $badWords = [];

    public function __construct()
    {
        $file = __DIR__ . '/../Resources/badwords.txt';
        $words = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
       
        $this->badWords = array_filter(
            array_map('trim', $words),
            fn($word) => mb_strlen($word) >= 3 
        );
    }

    public function containsBadWords(string $text): bool
    {
        $texteLowercase = mb_strtolower($text);
        
        foreach ($this->badWords as $word) {
            
            $pattern = '/\b' . preg_quote(mb_strtolower($word), '/') . '\b/u';
            if (preg_match($pattern, $texteLowercase)) {
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