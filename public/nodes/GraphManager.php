<?php

class GraphManager
{
    protected $config;

    public function getDefaultByFolder($folder)
    {
        return $this->config['default_by_folder_links'][ $folder ];
    }
}
