<?php
namespace Tsuruya;

class Letter {
    private string $char;
    private string $translit;
    private string $locale;
    private int $bytes;
    private int $bytesTranslit;
    private int $index;
    private int $pos;
    private int $posTranslit;

    public function __construct(string $char, string $locale, int $index, int $precPos = 0, int $precPosT = 0) {
        $this->char = $char;
        $this->locale = $locale;
        $oldLocale = setlocale(LC_ALL,"0");
        setlocale(LC_ALL, $this->locale);
        $tmp = str_replace('€', '&#@EUR@#&', $char);
        $tmp = iconv('UTF-8','ASCII//TRANSLIT', $tmp);
        $tmp = str_replace('&#@EUR@#&', '€', $tmp);
        $this->translit = $tmp;
        setlocale(LC_ALL, $oldLocale);
        $ord = ord($this->char);
        $this->bytes = $ord < 128 ? 1 : ($ord < 224 ? 2 : ($ord < 240 ? 3 : 4));
        $ord = ord($this->translit);
        $this->bytesTranslit = $ord < 128 ? 1 : ($ord < 224 ? 2 : ($ord < 240 ? 3 : 4));
        $this->index = $index;
        $this->pos = $this->index == 0 ? 0 : ($this->bytes + $precPos);
        $this->posTranslit = $this->index == 0 ? 0 : ($this->bytesTranslit + $precPosT);
    }

    public function get(bool $translit = false) { return $translit ? $this->translit : $this->char; }

    public function getChar() { return $this->get(false); }

    public function getTranslit() { return $this->get(true); }

    public function getIndex() { return $this->index; }

    public function getPos() { return $this->pos; }
    
    public function getPosT() { return $this->posTranslit; }
    
    public function getBytes() { return $this->bytes; }
}
?>