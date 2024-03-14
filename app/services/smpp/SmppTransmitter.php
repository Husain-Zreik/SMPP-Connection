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

    // protected function connect()
    // {
    //     // Create transport
    //     try {
    //         $this->transport = new SocketTransport([config('smpp.smpp_service')], config('smpp.smpp_port'));
    //         $this->transport->setRecvTimeout(30000);
    //         $this->transport->setSendTimeout(30000);

    //         // Create client
    //         $this->client = new SmppClient($this->transport);

    //         // Activate binary hex-output of server interaction
    //         $this->client->debug = true;
    //         $this->transport->debug = true;

    //         // Open the connection
    //         $this->transport->open();
    //         // dd($this->transport->isOpen());
    //     } catch (\SocketTransportException $th) {
    //         echo "not open ";
    //         throw $th;
    //     }

    //     $socket = $this->transport->getSocket();

    //     // Check if the socket resource is valid
    //     if (!is_resource($socket)) {
    //         // Get socket options
    //         $option = socket_get_option($socket, SOL_SOCKET, SO_ERROR);

    //         // Get local address and port
    //         socket_getsockname($socket, $localAddr, $localPort);

    //         // Get remote address and port
    //         socket_getpeername($socket, $remoteAddr, $remotePort);

    //         // Output socket information
    //         echo "Socket option: $option\n";
    //         echo "Local address: $localAddr\n";
    //         echo "Local port: $localPort\n";
    //         echo "Remote address: $remoteAddr\n";
    //         echo "Remote port: $remotePort\n";

    //         // Get the peer name (remote address and port)
    //         $peerAddress = '';
    //         $peerPort = 0;
    //         if (@socket_getpeername($socket, $peerAddress, $peerPort)) {
    //             echo "Connection established with $peerAddress on port $peerPort.";
    //         } else {
    //             echo "Failed to get peer name. Connection might not be established.";
    //             // Handle the error appropriately
    //         }
    //     } else {
    //         echo "Invalid socket resource.";
    //         // Handle the error appropriately, e.g., return or throw an exception
    //     }


    //     // Bind transmitter
    //     $this->client->bindTransmitter(config('smpp.smpp_transmitter_id'), config('smpp.smpp_transmitter_password'));
    // }

    protected function connect()
    {
        try {
            $this->transport = new SocketTransport([config('smpp.smpp_service')], config('smpp.smpp_port'));
            $this->transport->setRecvTimeout(30000);
            $this->transport->setSendTimeout(30000);
            $this->transport->open();

            // Get the Socket object from the transport
            $socket = $this->transport->getSocket();

            // Check if the socket is valid
            if ($socket instanceof SocketTransport) {
                // Get socket options
                $option = $this->transport->getSocketOption(0);

                // Get local address and port
                socket_getsockname($socket->getSocket(), $localAddr, $localPort);

                // Get remote address and port
                socket_getpeername($socket->getSocket(), $remoteAddr, $remotePort);

                // Output socket information
                echo "Socket option: $option\n";
                echo "Local address: $localAddr\n";
                echo "Local port: $localPort\n";
                echo "Remote address: $remoteAddr\n";
                echo "Remote port: $remotePort\n";

                // Initialize the SmppClient
                $this->client = new SmppClient($this->transport);

                // Bind transmitter
                $this->client->bindTransmitter(config('smpp.smpp_transmitter_id'), config('smpp.smpp_transmitter_password'));
            } else {
                echo "Invalid socket resource.";
                // Handle the error appropriately
            }
        } catch (\SocketTransportException $th) {
            echo "not open ";
            throw $th;
        }
    }


    public function sendSMS($source, $destination, $message)
    {
        if ($this->client === null) {
            // Handle the case where the client is not initialized
            echo "Client is not initialized.";
            return;
        }

        // Call the sendSMS method on the initialized client
        $this->client->sendSMS($source, $destination, $message);
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

    // public function sendSms($message, $from, $to)
    // {
    //     // Check if all parameters present
    //     if (!isset($message) || !isset($from) || !isset($to)) {
    //         // Handle missing parameters
    //         return 'Missing parameter';
    //     }

    //     // Encode parameters
    //     $encodedMessage = GsmEncoder::utf8_to_gsm0338($message);
    //     $fromAddress = new SmppAddress($from, SMPP::TON_ALPHANUMERIC);
    //     $toAddress = new SmppAddress($to, SMPP::TON_INTERNATIONAL, SMPP::NPI_E164);

    //     // Try to send message and catch exception
    //     try {
    //         $result = $this->client->sendSMS($fromAddress, $toAddress, $encodedMessage);

    //         // Check if sending was successful
    //         if ($result === true) {
    //             return "Message sent successfully";
    //         } else {
    //             // Handle sending failure
    //             logger()->error('Failed to send message');
    //             return "Failed to send message";
    //         }
    //     } catch (\Exception $e) {
    //         // Log the error and return an error message
    //         logger()->error('Error sending message: ' . $e->getMessage());
    //         return 'Error sending message: ' . $e->getMessage();
    //     }
    // }
}
