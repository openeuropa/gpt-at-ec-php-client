# GPT@EC PHP API client

This is an API client written in PHP to interact with the GPT@EC API.

### Important notes

The GPT@EC API is very similar to the Open AI API.\
Therefore, in order to ease the development, this library reuses the _openai-client/php_ where possible,
or extends it if needed.\
Since some parts of the _openai-client/php_ are marked as internal, this library might become incompatible
with future versions of it. Consider locking the _openai-client/php_ version, and update it to the latest
version reported as compatible in the test builds.

## Install

Use [Composer](https://getcomposer.org/) to require the library:

```shell
composer require openeuropa/gpt-at-ec-php-client
```

Make sure you have a PSR-18 client installed, or install one manually, e.g.:

```shell
composer require guzzlehttp/guzzle
```

The `php-http/discovery` plugin can also help installing and discovering a compatible PSR-18 client.

## Usage

You can create a new API client using the factory class. All you need is a GPT@EC API key:

```php
$api_key = getenv('KEY_AI_GPT_AT_EC');
$factory = new \Openeuropa\GptAtEcPhpClient\Factory();
$client = $factory->withApiKey($key)->make();
```

Now the client can be used to interact with the 4 available endpoints.

### Chat

The Chat Completions API endpoint will generate a model response from a list of messages comprising a
conversation.

Not all optional parameters are available for every model: GPT@EC applies a whitelist of forwarded
parameters per model family, and parameters not in the whitelist are silently ignored. In particular
`response_format` is not forwarded for any model, so structured output (JSON schema) is not available
through this endpoint. Use the [Responses](#responses) endpoint instead, which does not apply the
whitelist.

`create` method

Create a chat completion. The full model response will be returned at once by the API.

```php
$response = $client->chat()->create([
    'model' => 'gpt-4o',
    'messages' => [
    [
        'role' => 'system',
        'content' => 'You are a helpful assistant.',
    ],
    [
        'role' => 'user',
        'content' => 'Hello!',
    ],
]);

echo $response->id; // "chatcmpl-0123456789abcdef0123456789ab"
echo $result->object; // "chat.completion"
echo $result->created; // 1755523764
echo $result->model; // "gpt-4o-2000-01-01"

echo $response->choices[0]->message->role; // "assistant"
echo $response->choices[0]->message->content; // "Hello! How can I assist you today?"
echo $response->choices[0]->index; // 0
echo $response->choices[0]->finishReason; // "stop"

echo $response->usage->promptTokens; // 9
echo $response->usage->completionTokens; // 10
echo $response->usage->totalTokens; // 19
```

`createStreamed` method

Create a chat completion. The response will be streamed back in parts as the model generates it.

```php
$stream = $client->chat()->createStreamed([
    'model' => 'gpt-4o',
    'messages' => [
    [
        'role' => 'system',
        'content' => 'You are a helpful assistant.',
    ],
    [
        'role' => 'user',
        'content' => 'Hello!',
    ],
]);

foreach ($stream as $chunk) {
    echo $chunk->id; // "chatcmpl-0123456789abcdef0123456789ab"
    echo $chunk->object; // "chat.completion.chunk"
    echo $chunk->created; // 1755530359
    echo $chunk->model; // "meta-llama/Llama-3.3-70B-Instruct"

    echo $response->choices[0]->message->role; // "assistant"
    echo $response->choices[0]->message->content; // "Hello!", "How", "can", "I", "help", "you", "?"
}
```

### Models

Lists the various models available in the API.\
A single method is available, `list`.

```php
$response = $client->models()->list();

foreach ($response->data as $model) {
    echo $model->id; // "llama-3.3-70b-instruct"
    echo $model->name; // "LLama 3.3 70b instruct"
    echo $model->description; // "Very powerful open-weights model on par with the capabilities of GPT-4o for many types of tasks."
    print_r($model->sensitivityLevel); // ["PA", "SNC", "CU"]
    print_r($model->defaultFor); // ["SNC"]
    echo $model->created; // 1744201138356
    echo $model->ownedBy; // "meta"
    echo $model->object; // "model"
}
```

### Quota consumption

Provides the quota consumption details for a specific AI model.
A single method is available, `retrieve`.

```php
$response = $client->quotaConsumption()->retrieve('gpt-4o');

echo $response->consumedPromptTokens; // 1234
echo $response->consumedCompletionTokens; // 5678
echo $response->consumedTotalTokens; // 6912
echo $response->quota; // 100000
```

### Responses

Creates a model response through the Responses API.\
A single method is available, `create`. Streaming is not supported yet.

Unlike the Chat endpoint, the Responses endpoint does not apply a parameter whitelist: all parameters
from the request schema are forwarded to the model. This makes it the endpoint to use for structured
output, by passing a JSON schema as `text.format`.

Only some models support the Responses API. The API answers
`400 Model '...' does not support the Responses API` for the others.

```php
$response = $client->responses()->create([
    'model' => 'gpt-5.1',
    'input' => [
        [
            'role' => 'user',
            'content' => 'Give me a title and a body for a news item about the weather.',
        ],
    ],
    'text' => [
        'format' => [
            'type' => 'json_schema',
            'name' => 'news_item',
            'strict' => true,
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'title' => ['type' => 'string'],
                    'body' => ['type' => 'string'],
                ],
                'required' => ['title', 'body'],
                'additionalProperties' => false,
            ],
        ],
    ],
]);

echo $response->status; // "completed"
echo $response->outputText; // '{"title":"...","body":"..."}'
echo $response->text->format->name; // "news_item"
echo $response->usage->totalTokens; // 104
```

## Tests

The library is full covered by unit tests using the [PHPUnit](https://phpunit.de/) framework.
To run the tests, execute:

```shell
./vendor/bin/phpunit --coverage-html folder-for-coverage-results
```

## Versioning

We use [SemVer](http://semver.org/) for versioning.
