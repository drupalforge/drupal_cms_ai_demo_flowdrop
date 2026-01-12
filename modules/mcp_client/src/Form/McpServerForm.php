<?php

declare(strict_types=1);

namespace Drupal\mcp_client\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\CachedDiscoveryClearerInterface;
use Drupal\mcp_client\Entity\McpServer;
use Drupal\mcp_client\Service\McpClientFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * MCP Server form.
 */
final class McpServerForm extends EntityForm {

  /**
   * Constructs a new McpServerForm.
   *
   * @param \Drupal\mcp_client\Service\McpClientFactory $clientFactory
   *   The MCP client factory service.
   * @param \Drupal\Core\Plugin\CachedDiscoveryClearerInterface $pluginCacheClearer
   *   The plugin cache clearer service.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger service.
   */
  public function __construct(
    protected McpClientFactory $clientFactory,
    protected CachedDiscoveryClearerInterface $pluginCacheClearer,
    protected LoggerChannelInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('mcp_client.client_factory'),
      $container->get('plugin.cache_clearer'),
      $container->get('logger.channel.mcp_client')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $form
   * @phpstan-param \Drupal\Core\Form\FormStateInterface $form_state
   * @phpstan-return array<string, mixed>
   */
  public function form(array $form, FormStateInterface $form_state): array {

    $form = parent::form($form, $form_state);

    /** @var \Drupal\mcp_client\Entity\McpServer $entity */
    $entity = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $entity->label(),
      '#required' => TRUE,
      '#description' => $this->t('Label for the server for internal usage. Data name will prefix all tools.'),
      '#attributes' => [
        'placeholder' => 'My MCP Server',
      ],
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#machine_name' => [
        'exists' => [McpServer::class, 'load'],
      ],
      '#disabled' => !$entity->isNew(),
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $entity->status(),
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $entity->get('description'),
      '#description' => $this->t('A description of the server for internal purposes.'),
      '#rows' => 2,
    ];

    // Transport configuration fieldset.
    $form['transport'] = [
      '#type' => 'details',
      '#title' => $this->t('Transport Configuration'),
      '#open' => TRUE,
    ];

    $form['transport']['transport_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Transport Type'),
      '#options' => [
        'http' => $this->t('Streamable HTTP (recommended - works with modern MCP servers)'),
        'stdio' => $this->t('STDIO - Local process (for command-line MCP servers)'),
      ],
      '#default_value' => $entity->get('transport_type') ?? 'http',
      '#required' => TRUE,
      '#description' => $this->t('<strong>Use Streamable HTTP for remote MCP servers.</strong> STDIO is only for local command-line servers and should be used with caution.'),
      '#ajax' => [
        'callback' => '::transportTypeCallback',
        'wrapper' => 'transport-settings-wrapper',
        'event' => 'change',
      ],
    ];

    $form['transport']['settings'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'transport-settings-wrapper'],
    ];

    // Get current transport type (from AJAX or entity).
    $transport_type = $form_state->getValue(['transport', 'transport_type'])
      ?? $entity->get('transport_type')
      ?? 'http';

    // HTTP settings (use endpoint URL).
    if ($transport_type === 'http') {
      $form['transport']['settings']['endpoint'] = [
        '#type' => 'url',
        '#title' => $this->t('Endpoint URL'),
        '#default_value' => $entity->get('endpoint') ?? '',
        '#required' => TRUE,
        '#description' => $this->t('The HTTP endpoint URL (e.g., https://example.com/mcp).'),
        '#attributes' => [
          'placeholder' => 'https://example.com/mcp',
        ],
      ];

      $form['transport']['settings']['timeout'] = [
        '#type' => 'number',
        '#title' => $this->t('Timeout (seconds)'),
        '#default_value' => $entity->get('timeout') ?? 30,
        '#min' => 1,
        '#max' => 300,
        '#description' => $this->t('Connection timeout in seconds.'),
      ];

      // Advanced HTTP headers (collapsed).
      $form['transport']['settings']['advanced'] = [
        '#type' => 'details',
        '#title' => $this->t('Advanced Settings'),
        '#open' => FALSE,
      ];

      // HTTP Headers.
      $http_headers = $entity->get('http_headers') ?? [];

      $form['transport']['settings']['advanced']['http_headers'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('HTTP Headers'),
        '#tree' => TRUE,
        '#description' => $this->t('Add custom HTTP headers to send with requests.'),
      ];

      // Authorization header using Key module.
      $form['transport']['settings']['advanced']['http_headers']['Authorization_key'] = [
        '#type' => 'key_select',
        '#title' => $this->t('Authorization Key'),
        '#default_value' => $http_headers['Authorization_key'] ?? '',
        '#description' => $this->t('Select a key containing the authorization token.'),
        '#empty_option' => $this->t('- None -'),
      ];

      $form['transport']['settings']['advanced']['http_headers']['Authorization_prefix'] = [
        '#type' => 'select',
        '#title' => $this->t('Authorization Prefix'),
        '#options' => [
          'Bearer ' => $this->t('Bearer'),
          'Token ' => $this->t('Token'),
          'Basic ' => $this->t('Basic'),
          '' => $this->t('None (use raw token)'),
        ],
        '#default_value' => $http_headers['Authorization_prefix'] ?? 'Bearer ',
        '#description' => $this->t('Select the authorization scheme prefix.'),
        '#states' => [
          'visible' => [
            ':input[name="http_headers[Authorization_key]"]' => ['!value' => ''],
          ],
        ],
      ];
    }

    // STDIO settings (for local process execution).
    if ($transport_type === 'stdio') {
      $form['transport']['settings']['stdio_command'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Command'),
        '#default_value' => $entity->get('stdio_command') ?? '',
        '#required' => TRUE,
        '#description' => $this->t('Command to execute the MCP server (e.g., "node /path/to/server.js" or "python /path/to/server.py").'),
        '#attributes' => [
          'placeholder' => 'node /path/to/mcp-server.js',
        ],
      ];

      $form['transport']['settings']['stdio_cwd'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Working Directory'),
        '#default_value' => $entity->get('stdio_cwd') ?? '',
        '#description' => $this->t('Optional working directory for the process. Leave empty to use the system default.'),
        '#attributes' => [
          'placeholder' => '/path/to/working/directory',
        ],
      ];

      // Environment variables.
      $stdio_env = $entity->get('stdio_env') ?? [];

      $form['transport']['settings']['stdio_env'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Environment Variables'),
        '#tree' => TRUE,
        '#prefix' => '<div id="stdio-env-wrapper">',
        '#suffix' => '</div>',
      ];

      // Get the number of env vars from form state or entity.
      $num_env_vars = $form_state->get('num_env_vars');
      if ($num_env_vars === NULL) {
        $num_env_vars = !empty($stdio_env) ? count($stdio_env) : 1;
        $form_state->set('num_env_vars', $num_env_vars);
      }

      // Add existing env vars or empty fields.
      $env_keys = [];
      if (!empty($stdio_env)) {
        foreach ($stdio_env as $key => $value) {
          // Only add actual environment variable keys, not form elements.
          if (is_string($key) && !in_array($key, ['actions'])) {
            $env_keys[] = $key;
          }
        }
      }

      for ($i = 0; $i < $num_env_vars; $i++) {
        $env_key = $env_keys[$i] ?? '';
        $env_value = isset($env_keys[$i]) ? $stdio_env[$env_keys[$i]] : '';

        $form['transport']['settings']['stdio_env'][$i] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['container-inline']],
        ];

        $form['transport']['settings']['stdio_env'][$i]['name'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Variable Name'),
          '#default_value' => $env_key,
          '#size' => 25,
          '#placeholder' => 'YOUR_ENV_VAR_NAME',
        ];

        $form['transport']['settings']['stdio_env'][$i]['value_type'] = [
          '#type' => 'select',
          '#title' => $this->t('Type'),
          '#options' => [
            'plain' => $this->t('Plain text'),
            'key' => $this->t('Key'),
          ],
          '#default_value' => (is_array($env_value) && isset($env_value['type'])) ? $env_value['type'] : 'plain',
        ];

        $form['transport']['settings']['stdio_env'][$i]['value'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Value'),
          '#default_value' => is_array($env_value) ? ($env_value['value'] ?? '') : $env_value,
          '#size' => 40,
          '#placeholder' => 'your-value-here',
          '#states' => [
            'visible' => [
              ':input[name="stdio_env[' . $i . '][value_type]"]' => ['value' => 'plain'],
            ],
          ],
        ];

        $form['transport']['settings']['stdio_env'][$i]['key_id'] = [
          '#type' => 'key_select',
          '#title' => $this->t('Key'),
          '#default_value' => is_array($env_value) ? ($env_value['key_id'] ?? '') : '',
          '#states' => [
            'visible' => [
              ':input[name="stdio_env[' . $i . '][value_type]"]' => ['value' => 'key'],
            ],
          ],
        ];
      }

      $form['transport']['settings']['stdio_env']['actions'] = [
        '#type' => 'actions',
      ];

      $form['transport']['settings']['stdio_env']['actions']['add'] = [
        '#type' => 'submit',
        '#value' => $this->t('Add another variable'),
        '#submit' => ['::addEnvVar'],
        '#ajax' => [
          'callback' => '::envVarCallback',
          'wrapper' => 'stdio-env-wrapper',
        ],
        '#limit_validation_errors' => [],
      ];
    }

    $tools_available = $entity->get('tools') ?? [];
    $tools_to_pick = [];
    foreach ($tools_available as $tool) {
      $tools_to_pick[$tool['name']] = $tool['name'] . ' (' . $tool['description'] . ')';
    }

    $form['enabled_tools'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enabled tools'),
      '#description' => $this->t('Select the tools you want to enable. Save the form first to fetch available tools.'),
      '#options' => $tools_to_pick,
      '#default_value' => $entity->get('enabled_tools') ?? [],
      '#access' => !empty($tools_to_pick),
    ];

    if (empty($tools_to_pick) && !$entity->isNew()) {
      $form['tools_info'] = [
        '#markup' => '<p>' . $this->t('No tools available. Save the form to fetch tools from the server.') . '</p>',
      ];
    }

    $form['fetch_tools'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Fetch tools on save'),
      '#default_value' => TRUE,
      '#description' => $this->t('Attempt to connect to the server and fetch available tools when saving. Uncheck this if you want to configure the connection first without testing it.'),
    ];

    return $form;
  }

  /**
   * AJAX callback for transport type selection.
   *
   * @param array<string, mixed> $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array<string, mixed>
   *   The form element to return.
   */
  public function transportTypeCallback(array &$form, FormStateInterface $form_state): array {
    return $form['transport']['settings'];
  }

  /**
   * Submit handler to add another environment variable field.
   *
   * @param array<string, mixed> $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function addEnvVar(array &$form, FormStateInterface $form_state): void {
    $num_env_vars = $form_state->get('num_env_vars');
    $form_state->set('num_env_vars', $num_env_vars + 1);
    $form_state->setRebuild();
  }

  /**
   * AJAX callback for adding environment variables.
   *
   * @param array<string, mixed> $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array<string, mixed>
   *   The form element to return.
   */
  public function envVarCallback(array &$form, FormStateInterface $form_state): array {
    return $form['transport']['settings']['stdio_env'];
  }

  /**
   * {@inheritdoc}
   *
   * @param array<string, mixed> $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @phpstan-param array<string, mixed> $form
   * @param-out array<string, mixed> $form
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Parse HTTP headers before entity is built.
    $transport_type = $form_state->getValue('transport_type');
    if ($transport_type === 'http') {
      $http_headers_input = $form_state->getValue('http_headers');
      $http_headers = [];

      // Process Authorization header (key + prefix).
      if (!empty($http_headers_input['Authorization_key'])) {
        $http_headers['Authorization_key'] = $http_headers_input['Authorization_key'];
        $http_headers['Authorization_prefix'] = $http_headers_input['Authorization_prefix'] ?? 'Bearer ';
      }

      $form_state->setValue('http_headers', $http_headers);
    }

    // Parse environment variables before entity is built.
    if ($transport_type === 'stdio') {
      $stdio_env_input = $form_state->getValue('stdio_env');
      $stdio_env = [];

      if (!empty($stdio_env_input)) {
        foreach ($stdio_env_input as $key => $env_var) {
          // Skip non-numeric keys (like 'actions').
          if (!is_numeric($key) || empty($env_var['name'])) {
            continue;
          }

          $var_name = trim($env_var['name']);
          if (empty($var_name)) {
            continue;
          }

          // Check if Key module is enabled and if using a key reference.
          if (isset($env_var['value_type']) && $env_var['value_type'] === 'key' && !empty($env_var['key_id'])) {
            $stdio_env[$var_name] = [
              'type' => 'key',
              'key_id' => $env_var['key_id'],
            ];
          }
          else {
            // Plain text value.
            $stdio_env[$var_name] = [
              'type' => 'plain',
              'value' => $env_var['value'] ?? '',
            ];
          }
        }
      }

      // Replace form input with parsed array.
      $form_state->setValue('stdio_env', $stdio_env);
    }

    // @phpstan-ignore-next-line paramOut.type
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $form
   * @phpstan-param \Drupal\Core\Form\FormStateInterface $form_state
   * @phpstan-return int
   */
  public function save(array $form, FormStateInterface $form_state): int {
    /** @var \Drupal\mcp_client\Entity\McpServer $entity */
    $entity = $this->entity;

    // Extract and save transport settings.
    $transport_type = $form_state->getValue('transport_type');

    // Get the original transport type from the original entity if it exists.
    $old_transport_type = NULL;
    if (!$entity->isNew()) {
      /** @var \Drupal\mcp_client\Entity\McpServer|null $original */
      $original = $this->entityTypeManager
        ->getStorage('mcp_server')
        ->loadUnchanged($entity->id());
      if ($original) {
        $old_transport_type = $original->get('transport_type');
      }
    }

    // Check if transport type changed - if so, clear tools.
    if ($old_transport_type !== NULL && $old_transport_type !== $transport_type) {
      $entity->set('tools', []);
      $entity->set('enabled_tools', []);
      $this->messenger()->addWarning(
        $this->t('Transport type changed from @old to @new. Tools have been cleared and will be reloaded.', [
          '@old' => $old_transport_type,
          '@new' => $transport_type,
        ])
      );
    }

    $entity->set('transport_type', $transport_type);

    // Save transport-specific settings.
    if ($transport_type === 'http') {
      $endpoint = $form_state->getValue('endpoint');
      $timeout = $form_state->getValue('timeout');
      $http_headers = $form_state->getValue('http_headers');
      $entity->set('endpoint', $endpoint ?: '');
      $entity->set('timeout', $timeout ?: 30);
      $entity->set('http_headers', $http_headers ?: []);
      // Clear STDIO-specific fields.
      $entity->set('stdio_command', NULL);
      $entity->set('stdio_cwd', NULL);
      $entity->set('stdio_env', []);
    }
    elseif ($transport_type === 'stdio') {
      $stdio_command = $form_state->getValue('stdio_command');
      $stdio_cwd = $form_state->getValue('stdio_cwd');
      $stdio_env = $form_state->getValue('stdio_env');

      $entity->set('stdio_command', $stdio_command ?: NULL);
      $entity->set('stdio_cwd', $stdio_cwd ?: NULL);
      $entity->set('stdio_env', $stdio_env ?: []);
      // Clear endpoint for STDIO (not used).
      $entity->set('endpoint', '');
      // Clear HTTP headers for STDIO.
      $entity->set('http_headers', []);
    }

    // Try to fetch tools from the server.
    $fetch_tools = (bool) $form_state->getValue('fetch_tools');
    if ($fetch_tools) {
      try {
        $this->messenger()->addStatus($this->t('Attempting to connect to MCP server at @endpoint...', [
          '@endpoint' => $entity->get('endpoint'),
        ]));

        $client = $this->clientFactory->createFromEntity($entity);
        $tools = $client->listTools();
        if (count($tools) > 0) {
          // Set the tool name as key.
          $save_tools = [];
          foreach ($tools as $tool) {
            $save_tools[$tool['name']] = $tool;
          }
          $entity->set('tools', $save_tools);
          $this->messenger()->addStatus($this->t('Successfully fetched @count tools from the server.', [
            '@count' => count($tools),
          ]));
        }
        else {
          $this->messenger()->addWarning($this->t('Connected to server but no tools were found.'));
        }
      }
      catch (\Exception $e) {
        // Log the error but continue with save.
        $this->messenger()->addWarning(
          $this->t('Could not fetch tools from server: @message. You can configure tools later by editing the server.', [
            '@message' => $e->getMessage(),
          ])
        );
        $this->logger->error('Failed to fetch tools: @message. Trace: @trace', [
          '@message' => $e->getMessage(),
          '@trace' => $e->getTraceAsString(),
        ]);
      }
    }
    else {
      $this->messenger()->addWarning($this->t('Tool fetching skipped. You can fetch tools later by editing this server and enabling "Fetch tools on save".'));
    }

    $result = parent::save($form, $form_state);
    $message_args = ['%label' => $entity->label()];
    $message = match($result) {
      \SAVED_NEW => $this->t('Created new MCP Server %label.', $message_args),
      \SAVED_UPDATED => $this->t('Updated MCP Server %label.', $message_args),
      default => $this->t('Saved MCP Server %label.', $message_args),
    };
    $this->messenger()->addStatus($message);
    $form_state->setRedirectUrl($entity->toUrl('collection'));
    // Clear the plugin cache for AI function calls.
    $this->pluginCacheClearer->clearCachedDefinitions();

    return $result;
  }

}
