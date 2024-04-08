<?php

/**
 * Description of marc21

  @author Heinz

  for this to work PHP must have these modules enabled:

  intl
  mbstring


 *
 */
//
//  Returns a structure like the one below
//
//        $tagInd[0]->tag = '100';
//        $tagInd[0]->seq = $jj;
//        $tagInd[0]->ind = '1_';
//        $tagInd[0]->subs = Array();
//        $tagInd[0]->subs[0]->code = 'a';
//        $tagInd[0]->subs[0]->data = 'mein Buch';
//        $tagInd[0]->subs[1]->code = 'b';
//        $tagInd[0]->subs[1]->data = 'dein Buch';
//        
//
class m21File {

    private $fh, $filter, $leader, $dict, $data = [], $nRecords, $dataLen;
    public $recordOffset, $pos67, $error = '', $nonSortShow = false;

    function openM21($m21File) {
        if (file_exists($m21File)) {
            $this->fh = fopen($m21File, 'rb');
            $this->filter = '';
            $this->nRecords = 0;
            $this->data = [];
        } else {
            $this->error = "File " . basename($m21File) . " does not exist";
        }
    }

    function decodeRecord() {

        $tagInd = [];
        $m21 = $this->readM21Record();

        /**
         * **********
         * construct leader as tag '000'
         * ***********
         * */
        $oneTag = (object) '';
        $oneTag->tag = '000';
        $oneTag->ind = '  ';
        $oneTag->seq = '1';
        $oneTag->subs[0] = (object) '';
        $oneTag->subs[0]->code = 'a';
        $oneTag->subs[0]->data = $this->leader;

        if ($this->filter) {
            if (strpos($this->filter, '000') === false) {
                $oneTag = NULL;
            }
        }

        if ($oneTag !== NULL) {
            /*
             * ***********************************************
             *  save leader as tag '000'
             * **********************************************
             */
            $tagInd[] = $oneTag;
        }

        while ($m21) {
            $nTags = (strlen($this->dict) - 1) / 12;
            if (is_int($nTags) === false) {
                $this->error .= " Anzahl Tags $nTags keine ganze Zahl ";
                break;
            }
            /*
             * ***********************************************
             * iterate over directory entries, each 12 charactes
             * ***********************************************
             */
            $refTag = '';
            for ($j = 0, $i = 0; $j < $nTags; $j++) {
                $tag = substr($this->dict, $i, 3);
                if ($this->filter && $tag !== '001') {
                    if (strpos($this->filter, $tag) === false) {
                        $i += 12;
                        continue; //tag not in filter; skip it
                    }
                }
                $i += 3;
                $len = substr($this->dict, $i, 4) + 0;
                $i += 4;
                $offset = substr($this->dict, $i, 5) + 0;
                $i += 5;

                if ($tag != $refTag) {
                    $seq = 1;
                    $refTag = $tag;
                }
                $oneTag = (object) ['tag' => $tag, 'ind' => '  ', 'seq' => $seq, 'subs' => []]; //              
                /*
                 * ***********************************************
                 * indicators ?
                 * ***********************************************
                 */
                $seq++;
                if ($tag >= '010') {
                    $ind0 = $this->data[$offset++];
                    $ind1 = $this->data[$offset++];
                    $oneTag->ind = $ind0 . $ind1;
                }
                /*
                 * ***********************************************
                 * iterate over subfields separated by 'x1f' 
                 * ***********************************************
                 */
                $s = 0;
                $len = $offset + $len;
                while ($offset < $len && $this->data[$offset] !== "\x1E") {
                    $oneTag->subs[$s] = (object) ['code' => '', 'data' => ''];
                    if ($this->data[$offset] === "\x1F") {
                        /*
                         * ***********************************************
                         *  save subfield code 
                         * ***********************************************
                         */
                        $offset++;
                        $oneTag->subs[$s]->code = $this->data[$offset];
                        $offset++;
                    }

                    $myData = [];
                    while ($this->data[$offset] >= ' ') {
                        if (ord($this->data[$offset]) === 194) {
                            $do1 = ord($this->data[$offset + 1]);
                            if ($do1 === 152 || $do1 === 156) {
                                /*
                                 * ************
                                 * skip over  
                                 * NON-SORT BEGIN / START OF STRING UTF 8 as (HEX) 0xC2  0x98 (dec) 194 152
                                 * NON-SORT END / STRING TERMINATOR UTF 8 as (HEX) 0xC2  0x9C (dec) 194 156
                                 * Indicate this to the caller as '{{{' and }}}'. it is up to the caller how 
                                 * to deal with these patterns.
                                 * preg_replace('/\{\{\{.*\}\}\}/', ' ', $input_lines);
                                 * *************
                                 */
                                if ($this->nonSortShow) {
                                    $do1 === 152 ? $myData[] = '{{{' : $myData[] = '}}}';
                                }
                                $offset += 2;
                                continue;
                            }
                        }
                        $myData[] = $this->data[$offset];
                        $offset++;
                    }
                    /*
                     * ************
                     * save data
                     * *************
                     */
                    $oneTag->subs[$s]->data = Normalizer::normalize(implode('', $myData), Normalizer::FORM_C);
                    $s++;
                }
                $tagInd[] = $oneTag;
            }
            return $tagInd;
        }
        fclose($this->fh);
        return NULL;
    }

    function setTagFilter($filter) {
        if ($filter !== '') {
            $this->filter = ' ' . $filter . '|';
        }
    }

    function setPosition($offset) {
        if ($this->fh) {
            fseek($this->fh, $offset);
            $this->recordOffset = $offset;
        }
    }

    function skipRecord() {

        $this->recordOffset = ftell($this->fh);
        $this->leader = fread($this->fh, 24);
        if (feof($this->fh)) {
            return false;
        }
        $reclen = mb_substr($this->leader, 0, 5) * 1;
        $dataoffset = mb_substr($this->leader, 12, 5) * 1;
        if ($reclen - $dataoffset > 0) {
            fread($this->fh, $dataoffset - 24);
            fread($this->fh, $reclen - $dataoffset);
            $this->nRecords++;
            return true;
        }
        $this->nRecords++;
        $this->error .= "\r\nAb Record $this->nRecords, bei Offset  $this->recordOffset,  kann nicht weiter gelesen werden";
        return false;
    }

    /*
     * ***********************************************
     * private functions
     * ***********************************************
     */

    private function readM21Record() {

        $this->recordOffset = ftell($this->fh);
        $this->leader = fread($this->fh, 24);
        if (feof($this->fh)) {
            return false;
        }
        $this->pos67 = mb_substr($this->leader, 6, 2);
        $reclen = mb_substr($this->leader, 0, 5) * 1;
        $dataoffset = mb_substr($this->leader, 12, 5) * 1;
        if ($reclen - $dataoffset > 0) {
            $this->dict = fread($this->fh, $dataoffset - 24);
            $this->data = str_split(fread($this->fh, $reclen - $dataoffset));
            $this->dataLen = $reclen - $dataoffset;
            $this->nRecords++;
        } else {
            $this->error .= "\r\nAb Record $this->nRecords, bei Offeset  $this->recordOffset,  kann nicht weiter gelesen werden";
            return false;
        }
        return true;
    }
}
