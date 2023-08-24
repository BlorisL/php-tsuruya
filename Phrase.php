<?php
namespace Tsuruya;

include_once "Letter.php";

class Phrase {
    private string $locale;
    private string $phrase; // Original
    private string $phraseT = ''; // Translit
    private string $phraseF = ''; // Filtered
    private array $letters;
    private array $words;
    private \stdClass $replaces;

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
            $this->letters[] = new \Tsuruya\Letter($item, 'it_IT', $i, $pos, $posT);
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
        $tmp = $this->letters[0];
        if($method === false):
            $method = 'getPos'; // Original
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

    private function getPropLocale(?\stdClass $obj, string $property, string $type = '') {
        $tmp = null;
        if($obj instanceof \stdClass && isset($obj->{$property}) && ($obj->{$property} instanceof \stdClass)):
            $tmp = $obj->{$property};
        endif;
        return $this->getLocale($tmp, $type);
    }

    private function getLocale(?\stdClass $obj, string $type = '') {
        $tmp = null;
        if($obj instanceof \stdClass && isset($obj->{$this->locale})):
            $tmp = $obj->{$this->locale};
        endif;
        switch($type):
            case 'json': $tmp = !($tmp instanceof \stdClass) ? new \stdClass() : $tmp; break;
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
        if($json && !($result instanceof \stdClass)):
            $result = new \stdClass();
        endif;
        return $result;
    }
}
?>