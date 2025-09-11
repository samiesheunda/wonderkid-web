<?php

class PHP_Email_Form
{
    public $to;
    public $from_name;
    public $from_email;
    public $subject;
    public $ajax = false;
    public $smtp = false;
    private $messages = [];

    public function add_message($content, $label, $min_length = 0)
    {
        if ($min_length > 0 && strlen(trim($content)) < $min_length) {
            return false;
        }
        $this->messages[] = [
            'label' => $label,
            'content' => $content,
        ];
        return true;
    }

    public function send()
    {
        if (!$this->to || !$this->from_name || !$this->from_email || !$this->subject) {
            return $this->response(false, 'Required fields missing.');
        }
        if (!filter_var($this->from_email, FILTER_VALIDATE_EMAIL)) {
            return $this->response(false, 'Invalid email address.');
        }

        $email_subject = $this->subject;
        $email_body = "You have received a new message from the contact form.\n\n";
        foreach ($this->messages as $msg) {
            $email_body .= $msg['label'] . ': ' . $msg['content'] . "\n";
        }

        if ($this->smtp && is_array($this->smtp)) {
            // Use SMTP
            if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                if (!file_exists(__DIR__ . '/../../../vendor/autoload.php')) {
                    return $this->response(false, 'PHPMailer is required for SMTP support. Please install it using Composer.');
                }
                require_once __DIR__ . '/../../../vendor/autoload.php';
            }

            try {
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = $this->smtp['host'];
                $mail->SMTPAuth = true;
                $mail->Username = $this->smtp['username'];
                $mail->Password = $this->smtp['password'];
                $mail->Port = $this->smtp['port'];
                $mail->SMTPSecure = 'tls';

                $mail->setFrom($this->from_email, $this->from_name);
                $mail->addAddress($this->to);
                $mail->addReplyTo($this->from_email, $this->from_name);

                $mail->isHTML(false);
                $mail->Subject = $email_subject;
                $mail->Body = $email_body;

                $sent = $mail->send();
                if ($sent) {
                    return $this->response(true, 'Your message has been sent. Thank you!');
                }
            } catch (\Exception $e) {
                return $this->response(false, 'SMTP error: ' . $e->getMessage());
            }
        } else {
            // Use regular mail() function
            $headers = "From: " . $this->from_name . " <" . $this->from_email . ">\r\n";
            $headers .= "Reply-To: " . $this->from_email . "\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

            $sent = mail($this->to, $email_subject, $email_body, $headers);
            if ($sent) {
                return $this->response(true, 'Your message has been sent. Thank you!');
            }
        }

        return $this->response(false, 'Failed to send email. Please try again later.');
    }

    private function response($success, $message)
    {
        if ($this->ajax) {
            if ($success) {
                return 'OK';
            } else {
                return $message;
            }
        }
        return $message;
    }
}
