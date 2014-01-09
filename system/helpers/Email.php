<?php
/**
 * Copyright (c) 2013 Art & Logic, Inc. All Rights Reserved.
 * $Id$
 *
 * Allows us to create and send emails via mail or sendmail.
 */
namespace sanemethod\kiskit\system\helpers;

class EmailHelper{

    public $useragent = "KIS";
    public $mailpath = "/usr/sbin/sendmail"; // Sendmail path
    public $protocol = "mail"; // mail/sendmail
    public $wordwrap = TRUE; // TRUE/FALSE  Turns word-wrap on/off
    public $wrapchars = 76; // Number of characters to wrap at.
    public $mailtype = "text"; // text/html Either text or html
    public $charset = "utf-8"; // Default char set
    public $multipart = "mixed"; // "mixed" (in the body) or "related" (separate)
    public $altMessage = ''; // Alternative message for HTML emails
    public $priority = 3; // Default priority (1 - 5)
    public $newline = PHP_EOL;
    public $lastError = null;

    private $subject = "";
    private $body = "";
    private $finalBody = "";
    private $headerStr = "";
    private $replyTo = FALSE; // Whether we need to handle a reply-to path other than FROM
    private $recipients = array();
    private $headers = array();
    private $priorities = array('1 (Highest)', '2 (High)', '3 (Normal)', '4 (Low)', '5 (Lowest)');

    function __construct($config = null)
    {
        if (isset($config) && count($config) > 0)
        {
            foreach ($config as $key => $val)
            {
                if (isset($this->$key))
                {
                    $this->$key = $val;
                }
            }
        }
        $this->clear();
    }

    /**
     * Init the values for this specific email.
     *
     * @return $this
     */
    function clear()
    {
        $this->subject = "";
        $this->body = "";
        $this->finalBody = "";
        $this->headerStr = "";
        $this->recipients = array();
        $this->headers = array();

        $this->setHeader('User-Agent', $this->useragent);
        $this->setHeader('Date', $this->setDate());

        return $this;
    }

    /**
     * Set FROM line
     * @param string $from
     * @param string $name
     * @return $this
     */
    function from($from, $name = "")
    {
        if ($name == "") $name = $from;
        $this->setHeader('From', $name.' <'.$from.'>');
        $this->setHeader('Return-Path', '<'.$from.'>');

        return $this;
    }

    /**
     * Set Reply-To
     * @param $replyTo
     * @param string $name
     * @return $this
     */
    function replyTo($replyTo, $name = "")
    {
        if ($name == "") $name = $replyTo;
        $this->setHeader('Reply-To', $name.' <'.$replyTo.'>');
        $this->replyTo = TRUE;
        return $this;
    }

    /**
     * Set recipients.
     * @param $to Array
     * @return $this
     */
    function to($to)
    {
        $to = implode(", ", $to);

        if ($this->protocol != 'mail')
        {
            $this->setHeader('To', $to);
        }

        $this->recipients = $to;

        return $this;
    }

    /**
     * Set subject.
     * @param $subject
     * @return $this
     */
    function subject($subject)
    {
        $this->setHeader('Subject', $subject);
        return $this;
    }

    /**
     * Sets the body message.
     * @param $message
     * @return $this
     */
    function message($message)
    {
        $this->body = stripslashes(rtrim($message));
        return $this;
    }

    /**
     * Sends the email, either using PHP mail, or sendmail. Returns TRUE on success, FALSE otherwise.
     * @return bool
     */
    function send()
    {
        $this->writeHeaders();
        $this->buildPlainMessage();
        $this->buildMessage();

        switch($this->protocol)
        {
            case 'mail':
                if (! mail($this->recipients, $this->subject, $this->body, $this->headerStr))
                {
                    return FALSE;
                }
                return TRUE;
                break;
            case 'sendmail':
                $fp = @popen($this->mailpath . " -oi -f ".$this->headers['From']." -t", 'w');

                if ($fp === FALSE || $fp === NULL)
                {
                    return FALSE;
                }

                fputs($fp, $this->headerStr);
                fputs($fp, $this->body);

                $status = pclose($fp);
                if ($status != 0)
                {
                    return FALSE;
                }

                return TRUE;
                break;
        }
        return FALSE;
    }


    /**
     * Set header specified to value specified
     * @param $header
     * @param $value
     */
    private function setHeader($header, $value)
    {
        $this->headers[$header] = $value;
    }

    /**
     * Set RFC 822 Date
     * @return string
     */
    private function setDate()
    {
        $timezone = date("Z"); // offset in seconds, signed
        $operator = (strncmp($timezone, '-', 1) == 0) ? '-' : '+';
        $timezone = abs($timezone);
        $timezone = floor($timezone/3600) * 100 + ($timezone % 3600 ) / 60;

        return sprintf("%s %s%04d", date("D, j M Y H:i:s"), $operator, $timezone);
    }

    /**
     * Write the email headers.
     */
    private function writeHeaders()
    {
        if ($this->protocol == 'mail')
        {
            $this->subject = $this->headers['Subject'];
            unset($this->headers['Subject']);
        }

        $this->setHeader('X-Sender', $this->headers['From']);
        $this->setHeader('X-Mailer', $this->useragent);
        $this->setHeader('X-Priority', $this->priorities[$this->priority - 1]);
        $this->setHeader('Mime-Version', '1.0');
        $this->headerStr = "";

        foreach ($this->headers as $key => $val)
        {
            $val = trim($val);

            if ($val != "")
            {
                $this->headerStr .= $key.": ".$val.$this->newline;
            }
        }

        if ($this->protocol == 'mail')
        {
            $this->headerStr = rtrim($this->headerStr);
        }
    }

    /**
     * Build alternate plaintext message if none is provided for an html email by stripping out
     * the text from the body of the html message. If message is plaintext, simply wrap it.
     */
    private function buildPlainMessage(){
        if ($this->mailtype = 'html' && $this->altMessage == '')
        {
            // Strip plain text out of the html to create our plaintext alt message
            if (preg_match('/\<body.*?\>(.*)\<\/body\>/si', $this->body, $match))
            {
                $alt = $match[1];
            }
            else
            {
                $alt = $this->body;
            }
            $this->altMessage = trim(strip_tags($alt));
            $this->altMessage = wordwrap($this->altMessage, $this->wrapchars, "\n", FALSE);
        }
        else if ($this->mailtype = 'text')
        {
            $this->body = wordwrap($this->body, $this->wrapchars, "\n", FALSE);
        }
    }

    /**
     * Build out the message with the headers appropriately attributed and seperated/appended as needed.
     */
    private function buildMessage(){
        $hdr = ($this->protocol == 'mail') ? $this->newline : '';
        if ($this->mailtype == "html")
        {
            $hdr .= "Content-Type: text/html; charset=" . $this->charset . $this->newline;
            $hdr .= "Content-Transfer-Encoding: 8bit";

            $this->body = $this->body . $this->newline . $this->newline;

            if ($this->protocol == 'mail')
            {
                $this->headerStr .= $hdr;
            }
            else
            {
                $this->body = $hdr . $this->body;
            }
        }
        else
        {
            $hdr .= "Content-Type: text/plain; charset=" . $this->charset . $this->newline;
            $hdr .= "Content-Transfer-Encoding: 8bit";

            if ($this->protocol == 'mail')
            {
                $this->headerStr .= $hdr;
            }
            else
            {
                $this->body = $hdr . $this->newline . $this->newline . $this->body;
            }
        }
    }
}
