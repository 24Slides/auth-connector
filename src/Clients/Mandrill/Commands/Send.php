<?php

namespace Slides\Connector\Auth\Clients\Mandrill\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Fluent;
use Illuminate\Support\Str;
use Slides\Connector\Auth\Clients\Mandrill\Builders\Email;
use Slides\Connector\Auth\Clients\Mandrill\Client;
use Slides\Connector\Auth\Helpers\ConsoleHelper;

/**
 * Class Send
 *
 * @package Slides\Connector\Auth\Clients\Mandrill\Commands
 */
class Send extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mandrill:send
                           {--r|recipients=   : The list of recipients or name of the file with recipients }
                           {--t|template=     : Name of the email template }
                           {--p|params=       : The list of parameters for an email }
                           {--f|from=         : The sender email address and name }
                           {--apiToken=       : The Mandrill API token }
                           {--s|subject=      : The message subject }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a new transactional message through Mandrill using a template.';

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var array
     */
    protected $result = [
        'requests' => 0,
        'emails' => 0,
        'success' => 0,
        'failed' => [],
        'responses' => null
    ];

    /**
     * Execute the console command.
     *
     * @return void|null
     *
     * @throws Exception
     */
    public function handle()
    {
        $recipients = $this->resolveRecipients();

        $this->client = new Client([], array_filter([
            'secretKey' => $this->option('apiToken')
        ]));

        if (!$template = $this->option('template')) {
            throw new Exception('Please, pass the --template option');
        }

        $builder = new Email($template, $recipients, ConsoleHelper::stringToArray($this->option('params')));

        if ($from = $this->option('from')) {
            list($email, $name) = array_pad(explode(':', $from), 2, null);

            $builder->setFrom($email, $name);
        }

        if ($subject = $this->option('subject')) {
            $builder->setSubject($subject);
        }

        $users = $builder->getUsers();

        $this->info('Resolved ' . $users->count() . ' of ' . count($recipients) . ' recipients');

        if (!$this->confirm('Do you want to send ' . $users->count() . ' ' . Str::plural('email', $users->count()) . '?', true)) {
            return;
        }

        $start = time();

        $bar = $this->output->createProgressBar();
        $this->output->newLine('2');
        $bar->start($users->count());

        foreach ($builder->chunk(1000) as $email) {
            $this->parseResponse(
                $this->client->sendTemplate($email)
            );

            $bar->advance(count($email['recipients']));
        }

        $bar->finish();

        $this->output->newLine(2);

        $this->result['spent'] = time() - $start;

        $this->printResult(new Fluent($this->result));
    }

    /**
     * Resolve email recipients.
     *
     * @return array|null
     *
     * @throws Exception
     */
    protected function resolveRecipients(): ?array
    {
        if (!$option = $this->option('recipients')) {
            throw new Exception('Please, pass the --recipients option');
        }

        return Str::contains($option, '@')
            ? ConsoleHelper::stringToArray($option)
            : explode(PHP_EOL, file_get_contents($option));
    }

    /**
     * Parse Mandrill response.
     *
     * @param array $response
     *
     * @return void
     */
    protected function parseResponse(array $response): void
    {
        $result = collect($response);

        $this->result['requests']++;
        $this->result['emails'] += $result->count();
        $this->result['success'] += $result->where('status', 'sent')->count();

        $this->result['failed'] = $result->where('status', '<>', 'sent')->pluck('email')->merge($this->result['failed'])->toArray();
        $this->result['responses'] .= json_encode($response) . PHP_EOL;
    }

    /**
     * Print the result of execution.
     *
     * @param Fluent $result
     *
     * @return void
     */
    protected function printResult(Fluent $result): void
    {
        $this->warn('######################################################');

        $this->comment('Number of requests: ' . $result->get('requests'));
        $this->comment('Number of emails: ' . $result->get('emails'));
        $this->comment('Success: ' . $result->get('success'));
        $this->comment('Total time spent: ' . $result->get('spent') . 's');

        if ($failed = $result->get('failed')) {
            $filename = 'mandrill_failed_emails_' . time() . '.log';

            file_put_contents(storage_path('logs/' . $filename), implode(PHP_EOL, $failed));

            $this->output->newLine();
            $this->error('Failed emails saved to ' . $filename);
        }

        $responsesFilename = 'mandrill_responses_' . time() . '.log';

        file_put_contents(storage_path('logs/' . $responsesFilename), $result->get('responses'));

        $this->info('Responses saved to ' . $responsesFilename);
    }
}