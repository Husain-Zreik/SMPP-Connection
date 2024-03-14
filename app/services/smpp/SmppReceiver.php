<?php

namespace App\Services\Smpp;

use Illuminate\Support\Facades\Log;
use SmppClient;
use SmppDeliveryReceipt;
use SocketTransport;
use SocketTransportException;

class SmppReceiver
{
    protected $transport, $client, $transmitter;
    protected $socket;


    public function start()
    {
        $this->connect();
        $this->readSms();
    }

    protected function connect()
    {
        $this->transport = new SocketTransport([config('smpp.smpp_service')], config('smpp.smpp_port'), false, false);
        $this->transport->setRecvTimeout(30000);
        $this->transport->setSendTimeout(30000);

        // Create client
        $this->client = new SmppClient($this->transport);

        // Activate binary hex-output of server interaction
        $this->client->debug = true;
        $this->transport->debug = true;

        // Open the connection
        $this->transport->open();

        // Bind receiver
        $this->client->bindReceiver(config('smpp.smpp_receiver_id'), config('smpp.smpp_receiver_id'));
    }


    protected function disconnect()
    {
        if (isset($this->transport) && $this->transport->isOpen()) {
            if (isset($this->client)) {
                try {
                    $this->client->close();
                } catch (\Exception $e) {
                    $this->transport->close();
                }
            } else {
                $this->transport->close();
            }
        }
    }

    protected function keepAlive()
    {
        $this->client->enquireLink();
        $this->client->respondEnquireLink();
    }


    protected function readSms()
    {
        $time_start = microtime(true);
        $endtime = $time_start + 7; // 2 minutes
        $lastTime = 0;

        do {
            $res = $this->client->readSMS();
            // dd($res);
            if ($res) {
                try {
                    if ($res instanceof SmppDeliveryReceipt) {
                        // Handle delivery receipts
                    } else {
                        $from = $res->source->value;
                        $to = $res->destination->value;
                        $message = $res->message;
                        // Process received SMS message
                        dd($from, $message, $to);
                    }
                } catch (\Exception $e) {
                    // Handle specific exceptions or log general errors
                    Log::error('Error while processing SMS: ' . $e->getMessage());
                }
                $lastTime = time();
            } else {
                $this->client->respondEnquireLink();
            }
            // Keep connection alive every 30 seconds
            if (time() - $lastTime >= 30) {
                $this->keepAlive();
                $lastTime = time();
            }
        } while ($endtime > microtime(true));
    }
}
