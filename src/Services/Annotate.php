<?php

namespace Qklin\AutoRouter\Services;

class Annotate
{
    private $_lines;

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
