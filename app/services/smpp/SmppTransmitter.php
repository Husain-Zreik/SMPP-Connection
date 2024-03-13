<?php

namespace App\Services\Smpp;

use GsmEncoder;
use Illuminate\Support\Facades\Log;
use SMPP;
use SmppAddress;
use SmppClient;
use SocketTransport;

class SmppTransmitter
{
    protected $transport, $client, $credentialTransmitter;



    public function __construct()
    {

        $this->connect();
    }

    protected function test()
    {
        dd('here');
    }

    protected function connect()
    {

        try {
            $this->transport = new SocketTransport([config('smpp.smpp_service')], (int) config('smpp.smpp_port'));
            $this->transport->setRecvTimeout(30000);
            $this->transport->setSendTimeout(30000);

            $this->client = new SmppClient($this->transport);
            $this->client->debug = true;
            $this->transport->debug = true;

            $this->transport->open();

            // Corrected password parameter here
            $this->client->bindReceiver(config('smpp.smpp_receiver_id'), config('smpp.smpp_receiver_password'));
        } catch (\Exception $e) {
            Log::error('SMPP Connection Error: ' . $e->getMessage());
            throw $e;
        }
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

    public function keepAlive()
    {
        $this->client->enquireLink();
        $this->client->respondEnquireLink();
    }

    public function respond()
    {
        $this->client->respondEnquireLink();
    }

    public function sendSms($message, $from, $to)
    {
        if (!isset($message) || !isset($from) || !isset($to)) {
            dd('here');
        }

        $encodedMessage = GsmEncoder::utf8_to_gsm0338($message);
        $fromAddress = new SmppAddress($from, SMPP::TON_ALPHANUMERIC);
        $toAddress = new SmppAddress($to, SMPP::TON_INTERNATIONAL, SMPP::NPI_E164);

        try {
            $this->client->sendSMS($fromAddress, $toAddress, $encodedMessage);
            return;
        } catch (\Exception $e) {
            Log::error($e);
            Log::error($e->getMessage());
        }
    }
}
