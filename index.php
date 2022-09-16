<?php
ini_set('default_charset', '');
class Phrase {
    private string $locale;
    private string $phrase;
    private string $phraseT = '';
    private string $phraseF = '';
    private array $letters;
    private array $words;
    private stdClass $replaces;

    public function __construct(string $phrase, string $locale) {
        $this->phrase = $phrase;
        $this->locale = $locale;
        $this->letters = array();
        foreach(preg_split('//u', $phrase, -1, PREG_SPLIT_NO_EMPTY) as $i => $item):
            if($i == 0):
                $pos = 0;
                $posT = 0;
            else:
                $pos = $this->letters[$i - 1]->getPos();
                $posT = $this->letters[$i - 1]->getPosT();
            endif;
            $this->letters[] = new Letter($item, 'it_IT', $i, $pos, $posT);
        endforeach;
        $this->replaces = $this->getFile('replaces.json');
        $this->words = $this->getLocale($this->getFile('words.json'), 'array');
    }

    public function getPhrase(bool $translit = false) {
        $tmp = $translit ? $this->phraseT : $this->phrase;
        if(empty($tmp)):
            $tmp = '';
            foreach($this->letters as $letter):
                $tmp .= $letter->get($translit);
            endforeach;
        endif;
        return $tmp;
    }

    public function getPhraseT() { return $this->getPhrase(true); }

    public function getLetter(int $index, ?bool $method = null) {
        $tmp = null;
        if($method === false):
            $method = 'getPos'; // Normal
        elseif($method === true):
            $method = 'getPosT'; // Translit
        else:
            $method = 'getIndex'; // Index
        endif;
        foreach($this->letters as $letter):
            if($letter->{$method}() == $index):
                $tmp = $letter;
                break;
            endif;
        endforeach;
        return $tmp;
    }

    public function getLetterByPos(int $index, bool $translit = false) { return $this->getLetter($index, $translit); }

    public function getLetterByPosT(int $index) { return $this->getLetterByPos($index, true); }
    
    public function getWord(int $from, int $to, bool $translit = false) { 
        $tmp = '';
        $tot = $from + $to;
        for($i = $from; $i < $tot; $i++):
            $tmp .= $this->getLetter($i)->get($translit);
        endfor;
        return $tmp;
    }

    public function filter() {
        $pattern = '[\W_]*';
        $regex = array();
        foreach($this->words as $word):
            $tmp = str_split($word);
            $length = strlen($word);
            for($i = 0; $i < $length; $i++):
                $letter = $tmp[$i];
                $replaces = array('\?',strtolower($letter), strtoupper($letter));
                $tmpReplaces = $this->getPropLocale($this->replaces, $letter, 'array');
                if(!empty($tmpReplaces)):
                    $replaces = array_merge($replaces, $tmpReplaces);
                endif;
                $replace = '(?:' . (implode('|', $replaces)) . ')';
                $tmp[$i] = str_replace($letter, $replace, $tmp[$i]);
            endfor;
            $regex[] = implode($pattern, $tmp);
        endforeach;
        $regex = '/(?:(' . implode(')|(', $regex) . '))/mu';
        var_dump($regex);
        foreach(preg_split($regex, $this->getPhraseT(), -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY | PREG_SPLIT_OFFSET_CAPTURE)  as $item):
            preg_match($regex, $item[0], $matches);
            if(count($matches) > 1):
                $this->phraseF .= '###';
            else:
                $tmpSplit = preg_split('//mu', $item[0], -1, PREG_SPLIT_NO_EMPTY);
                $this->phraseF .= $this->getWord($this->getLetterByPosT($item[1])->getIndex(), count($tmpSplit));
            endif;
        endforeach;
        return $this->phraseF;
    }

    private function getPropLocale(?stdClass $obj, string $property, string $type = '') {
        $tmp = null;
        if($obj instanceof stdClass && isset($obj->{$property}) && ($obj->{$property} instanceof stdClass)):
            $tmp = $obj->{$property};
        endif;
        return $this->getLocale($tmp, $type);
    }

    private function getLocale(?stdClass $obj, string $type = '') {
        $tmp = null;
        if($obj instanceof stdClass && isset($obj->{$this->locale})):
            $tmp = $obj->{$this->locale};
        endif;
        switch($type):
            case 'json': $tmp = !($tmp instanceof stdClass) ? new stdClass() : $tmp; break;
            case 'array': $tmp = !is_array($tmp) ? array() : $tmp; break;
        endswitch;
        return $tmp;
    }

    private function getFile(string $filename, bool $json = true) {
        $result = null;
        if(file_exists($filename)):
            $tmp = file_get_contents($filename);
            if($json && $tmp):
                $tmp = json_decode($tmp);
            endif;
            if($tmp):
                $result = $tmp;
            endif;
        endif;
        if($json && !($result instanceof stdClass)):
            $result = new stdClass();
        endif;
        return $result;
    }
}

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

$phrase = 'Ho can3 ûn g#4*Tt(_______) IN Cå$...α Chë lîTiga sémpr€ CØL MIÕ Ç/-\n......€! AΑα';
$phrase = new Phrase($phrase, 'it_IT');
var_dump($phrase->getPhrase());
var_dump($phrase->filter());