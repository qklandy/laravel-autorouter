<?php

namespace Qklin\AutoRouter\Services;

class Annotate
{
    private $_lines;

    private $_docVars;

    public function __construct()
    {
        $this->_docVars = [
            'router'      => env('AR_DOCUMENT_ROUTER', 'arRouter'),
            'method'      => env('AR_DOCUMENT_METHOD', 'arMethod'),
            'only_inside' => env('AR_DOCUMENT_ONLY_INSIDE', 'arOnlyInside'),
            'deprecated'  => env('AR_DOCUMENT_DEPRECATED', 'deprecated'),
        ];
    }

    /**
     * @return array
     */
    public function getDocVar($key = null)
    {
        if (is_null($key)) {
            return $this->_docVars;
        }

        return $this->_docVars[$key] ?? "";
    }

    /**
     * 设置docvar
     * @param $key
     * @param $val
     */
    public function setDocVar($key, $val)
    {
        $this->_docVars[$key] = $val;
    }

    /**
     * 解析注解
     * @param $doc
     * @return array
     */
    public function parseSimple()
    {
        $params = [];
        if (empty($this->_lines)) {
            return $params;
        }

        foreach ($this->_lines as $line) {
            $line = trim($line);
            if (strpos($line, '@') === 0) {
                if (strpos($line, ' ') > 0) {
                    // 获取参数名和值
                    $param = substr($line, 1, strpos($line, ' ') - 1);
                    $value = trim(substr($line, strlen($param) + 2)); // Get the value
                } else {
                    $param = substr($line, 1);
                    $value = '';
                }

                $params[$param] = $value;
            } else {
                if (isset($params["doc_title"])) {
                    $params["doc_title"] .= PHP_EOL . $line;
                } else {
                    $params["doc_title"] = $line;
                }
            }
        }

        return $params;
    }

    /**
     * 解析行
     * @return array
     */
    public function parseLines($doc)
    {
        $this->_lines = [];
        if (preg_match('#^/\*\*(.*)\*/#s', $doc, $comment) === false) {
            return $this;
        }

        $comment = trim($comment[1] ?? "");
        if (!$comment) {
            return $this;
        }

        if (preg_match_all('#^\s*\*(.*)#m', $comment, $lines) === false) {
            return $this;
        }

        $this->_lines = $lines[1] ?? [];

        return $this;
    }
}
