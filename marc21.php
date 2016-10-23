<?php

/**
 * Description of marc21
 *
 * @author Heinz
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

    private $fh, $filter, $leader, $dict, $data = array(), $nRecords, $dataLen;
    public $recordOffset, $pos67;

    function __construct($m21File) {
        $this->fh = fopen($m21File, 'rb');
        $this->filter = '';
        $this->nRecords = 0;
    }

    function decodeRecord() {

        $tagInd = Array();
        $m21 = $this->readM21Record($this->fh);

        while ($m21) {
            $i = 0;
            $nTags = (strlen($this->dict) - 1) / 12;
            /*
             * ***********************************************
             * iterate over directory entries, each 12 charactes
             * ***********************************************
             */
            $refTag = '';
            for ($j = 0, $jj = -1, $i = 0; $j < $nTags; $j++, $i+=12) {
                $tag = mb_substr($this->dict, $i, 3);

                if ($this->filter) {
                    if (strpos($this->filter, $tag) === false) {
                        continue; //tag not in filter; skip it
                    }
                }

                $len = mb_substr($this->dict, $i + 3, 4) + 0;
                $offset = mb_substr($this->dict, $i + 3 + 4, 5) + 0;
                $jj++;
                if ($tag != $refTag) {
                    $seq = 1;
                    $refTag = $tag;
                }
                $tagInd[$jj] = (object) Array();
                $tagInd[$jj]->tag = $tag;
                /*
                 * ***********************************************
                 * indicators ?
                 * ***********************************************
                 */
                $tagInd[$jj]->ind = '  ';
                $tagInd[$jj]->seq = $seq++;
                if ($tag >= '010') {
                    $tagInd[$jj]->ind = '__';
                    if ($this->data[$offset] > ' ') {
                        $tagInd[$jj]->ind{0} = $this->data[$offset];
                    }
                    $offset++;
                    if ($this->data[$offset] > ' ') {
                        $tagInd[$jj]->ind{1} = $this->data[$offset];
                    }
                    if ($tagInd[$jj]->ind === '__') {
                        $tagInd[$jj]->ind = '';
                    }
                    $offset++;
                }
                /*
                 * ***********************************************
                 * iterate over subfields separated by 'x1f' 
                 * ***********************************************
                 */
                $s = 0;
                $len = $offset + $len;
                while ($offset < $len && $this->data[$offset] !== "\x1E") {
                    if ($this->data[$offset] === "\x1F") {
                        /*
                         * ***********************************************
                         *  save subfield code 
                         * ***********************************************
                         */
                        $tagInd[$jj]->subs[$s] = (object) Array();
                        $tagInd[$jj]->subs[$s]->code = $this->data[++$offset];
                        $offset++;
                    } else {
                        /*
                         * ***********************************************
                         *  no subfield code 
                         * ***********************************************
                         */
                        $tagInd[$jj]->subs[$s] = (object) Array();
                        $tagInd[$jj]->subs[$s]->code = '';
                    }
                    /*
                     * ************
                     * skip to end of data
                     * *************
                     */
                    $myData = Array();
                    $o = $offset;
                    while ($this->data[$o] >= ' ') {
                        if ((ord($this->data[$o]) === 194 && ord($this->data[$o + 1]) === 152 ) ||
                                (ord($this->data[$o]) === 194 && ord($this->data[$o + 1]) === 156 )) {
                            /*
                             * ************
                             * skip over  
                             * NON-SORT BEGIN / START OF STRING UTF 8 as (HEX) 0xC2  0x98 (dec) 194 152
                             * NON-SORT END / STRING TERMINATOR UTF 8 as (HEX) 0xC2  0x9C (dec) 194 156
                             * 
                             * *************
                             */
                            $o+=2;
                        } else {
                            $myData[] = $this->data[$o];
                            $o++;
                        }
                    }
                    /*
                     * ************
                     * save data
                     * *************
                     */
                    $tagInd[$jj]->subs[$s]->data = normalizer_normalize(trim(implode($myData)), Normalizer::FORM_C);
                    $offset = $o;
                    $s++;
                }
            }
            unset($myData);
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
        fseek($this->fh, $offset);
        $this->recordOffset = $offset;
    }

    /*
     * ***********************************************
     * private functions
     * ***********************************************
     */

    private final function readM21Record(&$fh) {
        $this->recordOffset = ftell($fh);
        $this->leader = fread($fh, 24);
        if (feof($fh)) {
            return false;
        }
        $this->pos67 = mb_substr($this->leader, 6, 2);
        $reclen = mb_substr($this->leader, 0, 5) * 1;
        $dataoffset = mb_substr($this->leader, 12, 5) * 1;
        $this->dict = fread($fh, $dataoffset - 24);
        $this->data = str_split(fread($fh, $reclen - $dataoffset));
        $this->dataLen = $reclen - $dataoffset;
        $this->nRecords++;
        return true;
    }

}
