<?php
defined('ABSPATH') || exit;

class WCPT_CSV_Exporter {

    protected $file_path;
    protected $handle;

    public function __construct($file_path) {
        $this->file_path = $file_path;
    }

    public function open(array $headers) {
        if (file_exists($this->file_path)) {
            unlink($this->file_path);
        }
        $this->handle = fopen($this->file_path, 'w');
        fputcsv($this->handle, $headers);
    }

    public function write_row(array $row) {
        if (!$this->handle) return;
        fputcsv($this->handle, $row);
    }

    public function close() {
        if ($this->handle) {
            fclose($this->handle);
        }
    }
}
