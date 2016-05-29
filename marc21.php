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
            for ($j = 0, $jj = -1, $i = 0; $j < $nTags; $j++, $i+=12) {
                $tag = mb_substr($this->dict, $i, 3);
                if ($this->filter && !strpos($this->filter, $tag . '|')) {
                    continue; //tag not in filter; skip it
                }
                $len = mb_substr($this->dict, $i + 3, 4) * 1;
                $offset = mb_substr($this->dict, $i + 3 + 4, 5) * 1;
                $jj++;
                $tagInd[$jj] = (new stdClass());
                $tagInd[$jj]->tag = $tag;
                /*
                 * ***********************************************
                 * indicators ?
                 * ***********************************************
                 */
                $tagInd[$jj]->ind = '  ';
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
                         *  save subfield code and data
                         * ***********************************************
                         */
                        $tagInd[$jj]->subs[$s] = new stdClass();
                        $tagInd[$jj]->subs[$s]->code = $this->data[++$offset];
                        $offset++;
                        while ($this->data[$offset] >= ' ') {
                            $tagInd[$jj]->subs[$s]->data.=$this->data[$offset++];
                        }
                        $s++;
                    } else {
                        /*
                         * ***********************************************
                         *  no subfield code , save data
                         * ***********************************************
                         */
                        $tagInd[$jj]->subs[$s] = new stdClass();
                        $tagInd[$jj]->subs[$s]->code = '';
                        while ($this->data[$offset] >= ' ') {
                            $tagInd[$jj]->subs[$s]->data.=$this->data[$offset];
                            $offset++;
                        }
                        $s++;
                    }
                }
            }
            return $tagInd;
        }
        fclose($this->fh);
        return NULL;
    }

    function setTagFilter($filter) {
        $this->filter = ' ' . $filter . '|';
    }

    /*
     * ***********************************************
     * private functions
     * ***********************************************
     */

    private final function readM21Record(&$fh) {
        $this->leader = fread($fh, 24);
        if (feof($fh)) {
            return false;
        }
        $reclen = mb_substr($this->leader, 0, 5) * 1;
        $dataoffset = mb_substr($this->leader, 12, 5) * 1;
        $this->dict = fread($fh, $dataoffset - 24);
        $this->data = str_split(fread($fh, $reclen - $dataoffset));
        $this->dataLen = $reclen - $dataoffset;
        $this->nRecords++;
        return true;
    }

}
