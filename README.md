# PocketFlow PHP

> PocketFlow PHP: Minimalist LLM framework for PHP. Let Agents build Agents!

**Language:** PHP | **License:** MIT

![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)
![PHP](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)
![ReactPHP](https://img.shields.io/badge/ReactPHP-Optional-orange.svg)
[![Total Downloads](http://poser.pugx.org/projectsaturnstudios/pocketflow-php/downloads)](https://packagist.org/packages/pocketflow-php/downloads)

PocketFlow PHP is the **first PHP implementation** of the [minimalist LLM framework](https://github.com/The-Pocket/PocketFlow) concept

- **Lightweight**: ~400 lines of PHP. Zero bloat, pure PHP elegance.
  
- **Framework Agnostic**: Works with any PHP project, not tied to specific frameworks.

- **Graph-Based**: Simple node and flow abstraction for complex LLM workflows.

- **ReactPHP Ready**: Optional async support for parallel processing.

Get started with PocketFlow PHP:
- **Installation**: `composer require projectsaturnstudios/pocketflow-php`
- **Quick Start**: Copy the [source files](https://github.com/projectsaturnstudios/pocketflow-php/tree/main/src) into your PHP project
- **Documentation**: Examples in this README and source code
- **LLM Integration**: Bring your own LLM client (OpenAI SDK, Guzzle, etc.)

## Why PocketFlow PHP?

The PHP ecosystem was missing a minimalist LLM workflow framework... until now!

<div align="center">

|                | **Abstraction**          | **PHP Integration**                                      | **LLM Support**                                    | **Lines**       | **Dependencies**    |
|----------------|:-----------------------------: |:-----------------------------------------------------------:|:------------------------------------------------------------:|:---------------:|:----------------------------:|
| LLPhant  | Comprehensive               | Framework agnostic <br><sup><sub>(Symfony/Laravel compatible)</sub></sup>              | Multiple providers <br><sup><sub>(OpenAI, Anthropic, Mistral, etc.)</sub></sup>                   | ~15K+          | Heavy (many providers)                     |
| LangChain PHP   | Agent, Chain                | Basic <br><sup><sub>(Work in progress)</sub></sup>         | Limited <br><sup><sub>(OpenAI, llama.cpp)</sub></sup>        | ~5K           | Moderate                     |
| **PocketFlow PHP** | **Graph**                    | **Framework Agnostic** <br><sup><sub>(Pure PHP, works anywhere)</sub></sup>                                                 | **Bring Your Own** <br><sup><sub>(Use any HTTP client)</sub></sup>                                                  | **~400**       | **Minimal**                  |

</div>

## How does PocketFlow PHP work?

The core abstraction: **Graph-based workflow execution** with simple nodes and flows.

### Core Components:

1. **BaseNode**: Foundation class with `prep()`, `exec()`, `post()` lifecycle
2. **Node**: Extended with retry logic and fallback handling  
3. **Flow**: Orchestrates node execution with action-based routing
4. **BatchNode/BatchFlow**: Process arrays of data through workflows
5. **AsyncNode/AsyncFlow**: ReactPHP-powered parallel execution (optional)

### Key Features:

- **Reference Passing**: Proper `&$shared` parameter handling for state persistence
- **Type Safety**: Full PHP 8.1+ type declarations
- **Error Handling**: Comprehensive exception handling with fallbacks
- **Memory Management**: Configurable data retention

## Examples

### Basic Hello World
```php
<?php
require 'vendor/autoload.php';

use ProjectSaturnStudios\PocketFlowPHP\{Node, Flow};

class HelloNode extends Node 
{
    public function exec(mixed $prep_res): mixed 
    {
        return "Hello, " . ($prep_res['name'] ?? 'World') . "!";
    }
}

class OutputNode extends Node 
{
    public function prep(mixed &$shared): mixed 
    {
        return $shared; // Pass through shared data
    }
    
    public function exec(mixed $prep_res): mixed 
    {
        echo $prep_res['greeting'] ?? 'No greeting found';
        return 'done';
    }
    
    public function post(mixed &$shared, mixed $prep_res, mixed $exec_res): mixed 
    {
        return $exec_res;
    }
}

// Create nodes
$helloNode = new HelloNode();
$outputNode = new OutputNode();

// Chain them
$helloNode->next($outputNode, 'success');

// Create flow and run
$flow = new Flow($helloNode);
$shared = ['name' => 'PocketFlow'];

$result = $flow->_run($shared);
```

### LLM Integration Example
```php
<?php
// Bring your own LLM client
use OpenAI\Client as OpenAIClient;

class LLMNode extends Node 
{
    public function __construct(private OpenAIClient $client) {}
    
    public function prep(mixed &$shared): mixed 
    {
        return ['prompt' => $shared['prompt'] ?? 'Say hello!'];
    }
    
    public function exec(mixed $prep_res): mixed 
    {
        $response = $this->client->chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'user', 'content' => $prep_res['prompt']]
            ]
        ]);
        
        return $response->choices[0]->message->content;
    }
    
    public function post(mixed &$shared, mixed $prep_res, mixed $exec_res): mixed 
    {
        $shared['llm_response'] = $exec_res;
        return 'success';
    }
}

// Usage
$client = OpenAI::client('your-api-key');
$llmNode = new LLMNode($client);
$outputNode = new OutputNode();

$llmNode->next($outputNode, 'success');
$flow = new Flow($llmNode);

$shared = ['prompt' => 'Write a haiku about PHP'];
$flow->_run($shared);
```

### Self-Looping Chat Bot
```php
<?php
class ChatNode extends Node 
{
    public function __construct(private $llmClient) {}
    
    public function prep(mixed &$shared): mixed 
    {
        // Get user input
        echo "You: ";
        $input = trim(fgets(STDIN));
        
        if ($input === 'exit') {
            return ['action' => 'exit'];
        }
        
        $shared['messages'][] = ['role' => 'user', 'content' => $input];
        return ['messages' => $shared['messages']];
    }
    
    public function exec(mixed $prep_res): mixed 
    {
        if ($prep_res['action'] === 'exit') {
            return 'exit';
        }
        
        // Call your LLM here
        $response = $this->llmClient->chat($prep_res['messages']);
        return $response;
    }
    
    public function post(mixed &$shared, mixed $prep_res, mixed $exec_res): mixed 
    {
        if ($exec_res === 'exit') {
            echo "Goodbye!\n";
            return 'exit';
        }
        
        echo "AI: $exec_res\n\n";
        $shared['messages'][] = ['role' => 'assistant', 'content' => $exec_res];
        
        return 'continue'; // Self-loop
    }
}

// Create self-looping chat
$chatNode = new ChatNode($yourLLMClient);
$chatNode->next($chatNode, 'continue'); // Self-loop!

$flow = new Flow($chatNode);
$shared = ['messages' => []];
$flow->_run($shared);
```

## Advanced Patterns

### Batch Processing
```php
$batchNode = new BatchNode();
$batchNode->setItems(['item1', 'item2', 'item3']);
$batchFlow = new BatchFlow($batchNode);
```

### Async Workflows (ReactPHP - Optional Dependency)
```php
// composer require react/socket
use React\EventLoop\Loop;

$asyncNode = new AsyncNode();
$asyncFlow = new AsyncFlow($asyncNode);
// Parallel execution with promises
```

### Conditional Routing
```php
$nodeA->next($nodeB, 'success');
$nodeA->next($nodeC, 'error'); 
$nodeA->next($nodeD, 'retry');
```

## Comparison with Original PocketFlow

| Feature | Python PocketFlow | PHP PocketFlow | Notes |
|---------|------------------|----------------|-------|
| Core Abstraction | ✅ Graph | ✅ Graph | Same philosophy |
| Async Support | ✅ asyncio | ⚠️ ReactPHP (optional) | Different implementations |
| Framework Integration | ❌ None | ✅ Framework Agnostic | Works with any PHP project |
| LLM Providers | ❌ Manual | ❌ Bring Your Own | Both require manual integration |
| Type Safety | ⚠️ Optional | ✅ Full | PHP 8.1+ strict types |
| Lines of Code | 100 | ~400 | More features, still minimal |

## Installation & Setup

### Requirements
- PHP 8.1+
- Composer

### Installation
```bash
composer require projectsaturnstudios/pocketflow-php
```

### Optional Dependencies
```bash
# For async support
composer require react/socket

# For LLM integration (examples)
composer require openai-php/client
composer require guzzlehttp/guzzle
```

### Quick Setup
1. **Install Package**: `composer require projectsaturnstudios/pocketflow-php`
2. **Create Nodes**: Extend `Node` or `BaseNode` classes
3. **Chain Workflows**: Use `$node->next($nextNode, 'action')`
4. **Run Flows**: `$flow = new Flow($startNode); $flow->_run($shared);`

## LLM Integration Notes

**Important**: PocketFlow PHP is **framework-agnostic** and does **not** include LLM provider integrations. You need to:

1. **Choose Your LLM Client**: OpenAI SDK, Guzzle HTTP, cURL, etc.
2. **Implement in Nodes**: Add LLM calls in your `exec()` methods
3. **Handle Responses**: Process LLM responses in your `post()` methods
4. **Manage State**: Use `&$shared` parameters for conversation history

This approach gives you **complete control** over your LLM integrations without vendor lock-in.

## Vendor Dependencies

**Dependencies:**
- **ReactPHP**: Required only for async features (optional)
- **PHP 8.1+**: Required for type safety and modern features

**No Lock-ins:**
- ❌ No specific LLM provider
- ❌ No specific HTTP client  
- ❌ No specific framework
- ❌ No specific database

## Contributing

This is the **world's first PHP implementation** of PocketFlow! We welcome contributions:

- 🐛 **Bug Reports**: Found an issue? Let us know!
- 🚀 **Feature Requests**: Ideas for PHP-specific features?
- 📖 **Documentation**: Help improve our docs
- 🧪 **Examples**: Share your PocketFlow PHP workflows

## Roadmap

- [x] **Core Framework**: Basic node and flow implementation
- [x] **Async Support**: ReactPHP integration
- [x] **Batch Processing**: Array and parallel processing
- [ ] **More Examples**: Real-world workflow patterns
- [ ] **Performance**: Optimize for large-scale applications  
- [ ] **Testing**: Comprehensive test suite
- [ ] **Documentation**: Full API documentation

## License

MIT License - same as original PocketFlow

## Acknowledgments

- **Original PocketFlow**: [The-Pocket/PocketFlow](https://github.com/The-Pocket/PocketFlow) - The inspiration and foundation
- **ReactPHP**: For async capabilities in PHP (optional dependency)
- **PHP Community**: For the amazing language ecosystem

---

Built with ADHD by Project Saturn Studios 
