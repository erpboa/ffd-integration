<?php

class Rss_XmlReader
{
    public $channelTitle = '';
    public $channelDesc = '';
    public $channelLink = '';
    public $items = array();
    public $xml;

    public $items_count;
    public $contents_load_time;
    public $parse_time;

    private $limit;
    private $page;
    private $total_pages;

    public function __construct($url = null, $posts_per_page=20, $current_page=1)
    {   
        $this->items_count = 0;
        $this->limit = $posts_per_page;
        $this->page = $current_page;
        $this->offset = ($this->page - 1) * $this->limit;
        if (null !== $url) {
            $this->load($url);

            if($posts_per_page != null ){
                $this->apply_pagination();
            } 
        }
    }
    

    public function load($url)
    {   
        $time = time();
        $this->xml = file_get_contents($url);
        $this->contents_load_time = time() - $time;
       
        $time = time();
        $xr = new XMLReader();
        $xr->XML($this->xml);
        $this->channelTitle = '';
        $this->channelDesc = '';
        $this->channelLink = '';
        $this->items = array();
        while ($xr->read()) {
            if (XMLReader::ELEMENT == $xr->nodeType) {
                switch ($xr->localName) {
                    case 'channel':
                        $this->_getChannelInfo($xr);
                        break;
                    case 'item':
                        $this->_getItem($xr);
                        break;
                }
            }
        }
        $this->xml = null;
        $this->parse_time = $time - time();

    }

    protected function _getChannelInfo($xr)
    {
        while ($xr->read() && ($xr->depth == 2)) {
            if (XMLReader::ELEMENT == $xr->nodeType) {
                switch ($xr->localName) {
                    case 'title':
                        $xr->read();
                        $this->channelTitle = $xr->value;
                        break;
                    case 'description':
                        $xr->read();
                        $this->channelDesc = $xr->value;
                        break;
                    case 'link':
                        $xr->read();
                        $this->channelLink = $xr->value;
                        break;
                }
            }
        }
    }


    protected function _getItem($xr)
    {
        $title = '';
        $link = '';
        $guid = '';
        $desc = '';
        $date = '';
        if( count($this->items) < $this->limit){

            while ($xr->read() && ($xr->depth > 2)) {
                if (XMLReader::ELEMENT == $xr->nodeType) {
                    switch ($xr->localName) {
                        case 'title':
                            $xr->read();
                            $title = $xr->value;
                            break;
                        case 'description':
                            $xr->read();
                            $desc = $xr->value;
                            break;
                        case 'link':
                            $xr->read();
                            $link = $xr->value;
                            break;
                        case 'guid':
                            $xr->read();
                            $guid = $xr->value;
                            break;
                        case 'date':
                            $xr->read();
                            $date = $xr->value;
                            break;
                    }
                }
            }
        
            if($this->items_count >= $this->offset ){
                $this->items[] = array(
                    'title' => $title,
                    'link' => $link,
                    'guid' => $guid,
                    'desc' => $desc,
                    'date' => $date
                );
            }

        }


        $this->items_count++;
    }


    public function apply_pagination(){


        $page = $this->page;
        $total = $this->items_count; //total items in array    
        $limit = $this->limit; //per page    
        $totalPages = ceil($total / $limit); //calculate total pages
        $page = max($page, 1); //get 1 page when $_GET['page'] <= 0
        $page = min($page, $totalPages); //get last page when $_GET['page'] > $totalPages
        $offset = ($page - 1) * $limit;
        if ($offset < 0) $offset = 0;

        $this->items = array_slice($this->items, $offset, $limit);
    }

}