<?php

namespace Slides\Connector\Auth\Clients\Mandrill\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Fluent;
use Illuminate\Support\Str;
use Slides\Connector\Auth\Clients\Mandrill\Client;
use Slides\Connector\Auth\Clients\Mandrill\Mailer;
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
     * @var Mailer
     */
    protected $mailer;

    public function __construct(Mailer $mailer)
    {
        $this->mailer = $mailer;

        parent::__construct();
    }

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

        if ($secret = $this->option('apiToken')) {
            $this->mailer = $this->mailer->setToken($secret);
        }

        if (!$template = $this->option('template')) {
            throw new Exception('Please, pass the --template option');
        }

        $builder = $this->mailer->template($template)
            ->recipients($recipients);

        if ($params = $this->option('params')){
            $builder->variables(explode(',', $params));
        }

        if ($from = $this->option('from')) {
            $builder->from(...array_pad(explode(':', $from), 2, null));
        }

        if ($subject = $this->option('subject')) {
            $builder->subject($subject);
        }

        if (!$this->confirm('Do you want to send ' . count($recipients) . ' emails?', true)) {
            return;
        }

        $start = time();

        $bar = $this->output->createProgressBar();
        $this->output->newLine('2');
        $bar->start(count($recipients));

        foreach ($builder->sendChunk(1000) as $response) {
            $this->parseResponse($response);

            $bar->advance(count($response));
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
        $this->result['success'] += $result->whereNotIn('status', ['rejected', 'invalid'])->count();

        $this->result['failed'] = $result->whereIn('status', ['rejected', 'invalid'])->pluck('email')->merge($this->result['failed'])->toArray();
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