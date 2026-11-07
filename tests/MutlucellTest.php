<?php

namespace Ardakilic\Mutlucell\Tests;

use Ardakilic\Mutlucell\Mutlucell;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use SimpleXMLElement;

class MutlucellTest extends TestCase
{
    private function xmlOf(array $history, int $index = 0): SimpleXMLElement
    {
        $this->assertArrayHasKey($index, $history);

        return new SimpleXMLElement((string) $history[$index]['request']->getBody());
    }

    #[Test]
    public function set_config_throws_when_auth_missing(): void
    {
        [$sut] = $this->makeSut();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Mutlucell auth parameter is not set in the config!');
        $sut->setConfig(['default_sender' => 'TEST']);
    }

    #[Test]
    public function set_config_throws_when_username_or_password_missing(): void
    {
        [$sut] = $this->makeSut();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Mutlucell Auth User and Password are not set correctly!');
        $sut->setConfig(['auth' => ['username' => 'u'], 'default_sender' => 'TEST']);
    }

    #[Test]
    public function set_config_throws_when_default_sender_missing(): void
    {
        [$sut] = $this->makeSut();

        // src reads default_sender before the isset check, silence that notice locally
        set_error_handler(static fn (): bool => true, E_WARNING);
        try {
            $sut->setConfig(['auth' => ['username' => 'u', 'password' => 'p']]);
            $this->fail('Expected exception was not thrown.');
        } catch (\Exception $e) {
            $this->assertSame('Mutlucell Default sender (originator) is not set!', $e->getMessage());
        } finally {
            restore_error_handler();
        }
    }

    #[Test]
    public function set_config_fills_defaults(): void
    {
        $history = [];
        [$sut, $history] = $this->makeSut([], ['$1#1.0'], $history);
        $sut->setConfig([
            'auth' => ['username' => 'u', 'password' => 'p'],
            'default_sender' => 'TEST',
        ]);

        $sut->send(receiver: '905551112233', message: 'hi');

        $xml = $this->xmlOf($history);
        $this->assertSame('default', (string) $xml['charset']);
        $this->assertFalse(isset($xml['addLinkToEnd']));
    }

    #[Test]
    public function set_config_sender_override(): void
    {
        $history = [];
        [$sut, $history] = $this->makeSut(['default_sender' => 'OVERRIDE'], ['$1#1.0'], $history);

        $sut->send(receiver: '905551112233', message: 'hi');

        $this->assertSame('OVERRIDE', (string) $this->xmlOf($history)['org']);
    }

    #[Test]
    public function send_returns_102_when_receiver_empty_without_http(): void
    {
        $history = [];
        [$sut, $history] = $this->makeSut([], [], $history);

        $this->assertSame(102, $sut->send(message: 'hello', receiver: ''));
        $this->assertSame(102, $sut->send(message: 'hello', receiver: '   '));
        $this->assertCount(0, $history);
    }

    #[Test]
    public function send_success(): void
    {
        $history = [];
        [$sut, $history] = $this->makeSut([], ['$123#1.0'], $history);

        $result = $sut->send(receiver: '905551112233', message: 'hello');

        $this->assertSame('$123#1.0', $result);
        $this->assertCount(1, $history);
        $request = $history[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringContainsString('sndblkex', (string) $request->getUri());
        $this->assertSame('text/xml; charset=UTF8', $request->getHeaderLine('Content-Type'));

        $xml = $this->xmlOf($history);
        $this->assertSame('testuser', (string) $xml['ka']);
        $this->assertSame('testpass', (string) $xml['pwd']);
        $this->assertSame('TEST', (string) $xml['org']);
        $this->assertSame('905551112233', (string) $xml->mesaj->nums);
        $this->assertSame('hello', (string) $xml->mesaj->metin);
    }

    #[Test]
    public function send_failure_status(): void
    {
        [$sut] = $this->makeSut([], ['$123#0.0']);

        $this->assertSame('$123#0.0', $sut->send(receiver: '905551112233', message: 'hello'));
    }

    #[Test]
    public function send_numeric_error(): void
    {
        [$sut] = $this->makeSut([], ['22']);

        $this->assertSame('22', $sut->send(message: 'hello', receiver: '905551112233'));
    }

    #[Test]
    public function send_with_scheduled_date(): void
    {
        $history = [];
        [$sut, $history] = $this->makeSut([], ['$1#1.0'], $history);

        $sut->send(receiver: '905551112233', message: 'hi', date: '2026-05-01 10:00:00');

        $this->assertSame('2026-05-01 10:00:00', (string) $this->xmlOf($history)['tarih']);
    }

    #[Test]
    public function send_with_custom_sender(): void
    {
        $history = [];
        [$sut, $history] = $this->makeSut([], ['$1#1.0'], $history);

        $sut->send(receiver: '905551112233', message: 'hi', sender: 'CUSTOM');

        $this->assertSame('CUSTOM', (string) $this->xmlOf($history)['org']);
    }

    #[Test]
    public function send_empty_message_becomes_ampersand(): void
    {
        // Bare '&' cannot be serialized by SimpleXMLElement::addChild, so assert
        // the internal state via the protected initializer instead of send().
        [$sut] = $this->makeSut();
        $init = new \ReflectionMethod(Mutlucell::class, 'initSenderAndMessage');
        $init->setAccessible(true);
        $init->invoke($sut, message: '', sender: '');

        $message = new \ReflectionProperty(Mutlucell::class, 'message');
        $message->setAccessible(true);

        $this->assertSame('&', $message->getValue($sut));
    }

    #[Test]
    public function send_bulk_with_array_recipients(): void
    {
        $history = [];
        [$sut, $history] = $this->makeSut([], ['$1#1.0'], $history);

        $result = $sut->sendBulk(recipients: ['905551112233', '905544443322'], message: 'bulk hi');

        $this->assertSame('$1#1.0', $result);
        $request = $history[0]['request'];
        $this->assertStringContainsString('sndblkex', (string) $request->getUri());
        $this->assertSame('905551112233, 905544443322', (string) $this->xmlOf($history)->mesaj->nums);
        $this->assertSame('bulk hi', (string) $this->xmlOf($history)->mesaj->metin);
    }

    #[Test]
    public function send_bulk_rejects_string_recipients(): void
    {
        [$sut] = $this->makeSut();

        $this->expectException(\TypeError::class);
        $sut->sendBulk(recipients: '905551112233', message: 'hi');
    }

    #[Test]
    public function send_multi(): void
    {
        $history = [];
        [$sut, $history] = $this->makeSut([], ['$1#1.0'], $history);

        $sut->sendMulti(messages: ['905551112233' => 'msg1', '905544443322' => 'msg2']);

        $xml = $this->xmlOf($history);
        $this->assertSame('TEST', (string) $xml['org']);
        $this->assertCount(2, $xml->mesaj);
        $this->assertSame('905551112233', (string) $xml->mesaj[0]->nums);
        $this->assertSame('msg1', (string) $xml->mesaj[0]->metin);
        $this->assertSame('905544443322', (string) $xml->mesaj[1]->nums);
        $this->assertSame('msg2', (string) $xml->mesaj[1]->metin);
    }

    #[Test]
    public function send_multi_uses_custom_sender(): void
    {
        $history = [];
        [$sut, $history] = $this->makeSut([], ['$1#1.0'], $history);

        $sut->sendMulti(messages: ['905551112233' => 'msg1'], sender: 'CUSTOM');

        $this->assertSame('CUSTOM', (string) $this->xmlOf($history)['org']);
    }

    #[Test]
    public function add_blacklist_rejects_string(): void
    {
        [$sut] = $this->makeSut();

        $this->expectException(\TypeError::class);
        $sut->addBlacklist(phoneNumbers: '905551112233');
    }

    #[Test]
    public function add_blacklist_array(): void
    {
        $history = [];
        [$sut, $history] = $this->makeSut([], ['added'], $history);

        $sut->addBlacklist(phoneNumbers: ['905551112233', '905544443322']);

        $this->assertStringContainsString('addblklst', (string) $history[0]['request']->getUri());
        $this->assertSame('905551112233, 905544443322', (string) $this->xmlOf($history)->nums);
    }

    #[Test]
    public function add_blacklist_default_empty(): void
    {
        $history = [];
        [$sut, $history] = $this->makeSut([], ['added'], $history);

        $sut->addBlacklist();

        $this->assertSame('', (string) $this->xmlOf($history)->nums);
    }

    #[Test]
    public function delete_blacklist_rejects_string(): void
    {
        [$sut] = $this->makeSut();

        $this->expectException(\TypeError::class);
        $sut->deleteBlacklist(phoneNumbers: '905551112233');
    }

    #[Test]
    public function delete_blacklist_array(): void
    {
        $history = [];
        [$sut, $history] = $this->makeSut([], ['deleted'], $history);

        $sut->deleteBlacklist(phoneNumbers: ['905551112233', '905544443322']);

        $this->assertStringContainsString('dltblklst', (string) $history[0]['request']->getUri());
        $this->assertSame('905551112233, 905544443322', (string) $this->xmlOf($history)->nums);
    }

    #[Test]
    public function delete_blacklist_empty_requires_confirm(): void
    {
        [$sut] = $this->makeSut();

        $this->expectException(\InvalidArgumentException::class);
        $sut->deleteBlacklist();
    }

    #[Test]
    public function delete_blacklist_confirmed_empty_removes_all(): void
    {
        $history = [];
        [$sut, $history] = $this->makeSut([], ['deleted'], $history);

        $sut->deleteBlacklist(confirm: true);

        $this->assertSame('', (string) $this->xmlOf($history)->nums);
    }

    #[Test]
    public function check_balance(): void
    {
        $history = [];
        [$sut, $history] = $this->makeSut([], ['$1986.0'], $history);

        $this->assertSame(1986, $sut->checkBalance());
        $this->assertStringContainsString('gtcrdtex', (string) $history[0]['request']->getUri());
    }

    #[Test]
    public function list_originators(): void
    {
        $history = [];
        [$sut, $history] = $this->makeSut([], ['TEST,INFO'], $history);

        $this->assertSame('TEST,INFO', $sut->listOriginators());
        $this->assertStringContainsString('gtorgex', (string) $history[0]['request']->getUri());
    }

    #[Test]
    public function get_message_report_numeric_errors(): void
    {
        $expected = [
            20 => 'The xml that has been posted is misformatted.',
            23 => 'Username or password is incorrect.',
            30 => 'Account is not activated yet.',
        ];

        foreach ($expected as $code => $message) {
            $history = [];
            [$sut, $history] = $this->makeSut([], [(string) $code], $history);

            $report = $sut->getMessageReport(messageId: '1');

            $this->assertFalse($report['success']);
            $this->assertSame($code, $report[0]['error_code']);
            $this->assertSame($message, $report[0]['error_message']);
            $this->assertStringContainsString('gtblkrprtex', (string) $history[0]['request']->getUri());
        }
    }

    #[Test]
    public function get_message_report_success(): void
    {
        [$sut] = $this->makeSut([], ["905551112233 3\n905544443322 99\n"]);

        $report = $sut->getMessageReport(messageId: '1');

        $this->assertTrue($report['success']);
        $this->assertSame(
            ['success' => true, 'number' => '905551112233', 'result' => '3', 'result_text' => 'Successful'],
            $report['numbers']['905551112233']
        );
        $this->assertSame('Unknown', $report['numbers']['905544443322']['result_text']);
    }

    #[Test]
    public function parse_output(): void
    {
        [$sut] = $this->makeSut();

        $this->assertSame('The xml that has been posted is misformatted.', $sut->parseOutput(output: '20'));
        $this->assertSame("You don't own the originator (sender ID)", $sut->parseOutput(output: '21'));
        $this->assertSame('Your balance is insufficent', $sut->parseOutput(output: '22'));
        $this->assertSame('Username or password is incorrect.', $sut->parseOutput(output: '23'));
        $this->assertSame('There is another action happening on your account', $sut->parseOutput(output: '24'));
        $this->assertSame('Please try again in a couple of minutes later', $sut->parseOutput(output: '25'));
        $this->assertSame('Account is not activated yet.', $sut->parseOutput(output: '30'));
        $this->assertSame('You must provide a message', $sut->parseOutput(output: '100'));
        $this->assertSame('Recipent(s) must be an array', $sut->parseOutput(output: '101'));
        $this->assertSame('Misformatted Recipent(s), all of them have to be an integer', $sut->parseOutput(output: '102'));
        $this->assertSame('Unknwon error', $sut->parseOutput(output: '999'));
        $this->assertSame('SMS have been sent successfully!', $sut->parseOutput(output: '$456#1.0'));
        $this->assertSame('No SMS sent', $sut->parseOutput(output: '$456#0.0'));
        $this->assertSame('something-else', $sut->parseOutput(output: 'something-else'));
        $this->assertSame('Misformatted Recipent(s), all of them have to be an integer', $sut->parseOutput(output: 102));
        $this->assertSame('Unknwon error', $sut->parseOutput(output: '45'));
        $this->assertSame('Unknwon error', $sut->parseOutput(output: 45));
    }

    #[Test]
    public function is_num_rejects_arrays(): void
    {
        [$sut] = $this->makeSut();
        $isNum = new \ReflectionMethod(Mutlucell::class, 'isNum');
        $isNum->setAccessible(true);

        $this->assertFalse($isNum->invoke($sut, []));
    }

    #[Test]
    public function get_status(): void
    {
        [$sut] = $this->makeSut();

        $this->assertFalse($sut->getStatus(output: '22'));
        $this->assertFalse($sut->getStatus(output: '$1#0.0'));
        $this->assertTrue($sut->getStatus(output: '$1#1.0'));
        $this->assertFalse($sut->getStatus(output: 'garbage'));
        $this->assertFalse($sut->getStatus(output: 102));
    }

    #[Test]
    public function get_message_id(): void
    {
        [$sut] = $this->makeSut();

        $this->assertSame('987', $sut->getMessageId(output: '$987#1.0'));
        $this->assertNull($sut->getMessageId(output: 'garbage'));
    }

    #[Test]
    public function queue_path_returns_queued_without_http(): void
    {
        $history = [];
        [$sut, $history] = $this->makeSut(['queue' => true], [], $history);
        Queue::fake();

        $this->assertSame('queued', $sut->send(receiver: '905551112233', message: 'hi'));
        $this->assertCount(0, $history);
    }

    #[Test]
    public function charset_and_unsubscribe_link_attributes(): void
    {
        $history = [];
        [$sut, $history] = $this->makeSut(
            ['charset' => 'turkish', 'append_unsubscribe_link' => true],
            ['$1#1.0'],
            $history
        );

        $sut->send(receiver: '905551112233', message: 'hi');

        $xml = $this->xmlOf($history);
        $this->assertSame('turkish', (string) $xml['charset']);
        $this->assertSame('true', (string) $xml['addLinkToEnd']);
    }
}
