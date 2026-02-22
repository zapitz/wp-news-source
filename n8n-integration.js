// WP News Source - n8n Code Node Integration
// Copy this entire code into a Code node in n8n
// Version: 2.4.0

// CONFIGURATION - Update these values
const CONFIG = {
  siteUrl: 'https://your-site.com', // Your WordPress site URL (no trailing slash)
  apiKey: 'your-api-key-here', // Get from WP News Source Settings
  requireApiKey: true // Set to false if API key is not required
};

// Helper function to make API requests
async function wpNewsSourceAPI(endpoint, method = 'GET', data = null) {
  const url = `${CONFIG.siteUrl}/wp-json/wp-news-source/v1/${endpoint}`;
  
  const headers = {
    'Content-Type': 'application/json'
  };
  
  // Add API key if required
  if (CONFIG.requireApiKey) {
    headers['X-API-Key'] = CONFIG.apiKey;
  }
  
  const options = {
    method: method,
    headers: headers
  };
  
  if (data && method !== 'GET') {
    options.body = JSON.stringify(data);
  }
  
  try {
    const response = await fetch(url, options);
    const result = await response.json();
    
    if (!response.ok) {
      throw new Error(result.message || `API Error: ${response.status}`);
    }
    
    return result;
  } catch (error) {
    throw new Error(`WP News Source API Error: ${error.message}`);
  }
}

// AVAILABLE FUNCTIONS

// 1. Get all sources with their mappings
async function getAllSources() {
  return await wpNewsSourceAPI('mapping');
}

// 2. Detect source from content (uses AI detection rules if configured)
async function detectSource(content, title = '') {
  return await wpNewsSourceAPI('detect', 'POST', {
    content: content,
    title: title
  });
}

// 3. Validate content against source rules (AI-based validation)
async function validateContent(sourceId, content, title = '') {
  return await wpNewsSourceAPI('validate', 'POST', {
    source_id: sourceId,
    content: content,
    title: title
  });
}

// 4. Search posts with filters
async function searchPosts(params = {}) {
  // Available params:
  // - title: Search in title
  // - date_from: Start date (YYYY-MM-DD)
  // - date_to: End date (YYYY-MM-DD)
  // - category_id: Filter by category
  // - tag_ids: Array of tag IDs
  // - limit: Max results (default 10, max 50)
  // - status: 'publish', 'draft', or 'any'
  
  return await wpNewsSourceAPI('search-posts', 'POST', params);
}

// 5. Create a complete post with all features
async function createPost(params) {
  // Required params:
  // - title: Post title
  // - content: Post content (HTML)
  
  // Optional params:
  // - source_id: Auto-apply source settings
  // - source_name: Detect source by name
  // - category_ids: Array of category IDs
  // - tag_ids: Array of tag IDs
  // - tag_names: Array of tag names (creates if not exist)
  // - author_id: WordPress user ID (default 1)
  // - featured_image_url: Image URL to download
  // - featured_image_base64: Base64 image data
  // - telegram_file_id: Telegram file ID for images
  // - attachment_id: Existing WordPress media ID
  // - status: 'publish', 'draft', 'pending', 'private'
  // - post_date: Custom date (YYYY-MM-DD HH:MM:SS)
  // - meta_fields: Object with custom fields
  
  return await wpNewsSourceAPI('create-post', 'POST', params);
}

// 6. Upload image independently
async function uploadImage(params) {
  // Params:
  // - url: Image URL to download
  // - base64: Base64 image data
  // - telegram_file_id: Telegram file ID
  // - filename: Optional filename
  
  return await wpNewsSourceAPI('upload-image', 'POST', params);
}

// 7. Webhook notification (for testing)
async function triggerWebhook(sourceId, data) {
  return await wpNewsSourceAPI(`webhook/${sourceId}`, 'POST', data);
}

// 8. Get detection history
async function getHistory(sourceId = null, limit = 100) {
  let endpoint = 'history';
  if (sourceId) {
    endpoint += `?source_id=${sourceId}&limit=${limit}`;
  }
  return await wpNewsSourceAPI(endpoint);
}

// 9. Get statistics
async function getStats() {
  return await wpNewsSourceAPI('stats');
}

// 10. Export/Import sources
async function exportSources() {
  return await wpNewsSourceAPI('export');
}

async function importSources(sourcesJson) {
  return await wpNewsSourceAPI('import', 'POST', {
    sources: sourcesJson
  });
}

// UNDERSTANDING AI DETECTION RULES
// Sources can have detection_rules in JSON format for AI-based detection
// Example structure:
/*
{
  "tipo_fuente": "gobierno|empresa|ong|otro",
  "identificadores": {
    "principales": ["Name variations", "Official names"],
    "contexto_requerido": ["Required context words"],
    "combinaciones": ["Word combinations"]
  },
  "contexto": {
    "nivel": "municipal|estatal|federal",
    "jurisdiccion": "Location/jurisdiction",
    "sector": "government|private|social"
  },
  "reglas_validacion": [
    "MUST contain X",
    "MUST be in context of Y"
  ],
  "exclusiones": ["Words that invalidate detection"]
}
*/

// MAIN EXECUTION - EXAMPLES FOR n8n

// Get the action from your workflow
const action = $input.first().json.action || 'help';
const inputData = $input.first().json;

// Execute based on action
switch(action) {
  
  // SCENARIO 1: Detect source and prepare post data
  case 'detect_and_prepare':
    const detection = await detectSource(inputData.content, inputData.title);
    
    if (detection.detected) {
      return {
        json: {
          action: 'source_detected',
          source: detection.source,
          confidence: detection.confidence,
          detection_method: detection.method,
          ai_analysis: detection.ai_analysis, // If AI rules were used
          post_data: {
            title: inputData.title,
            content: inputData.content,
            category_ids: [detection.instructions.category_id],
            tag_ids: detection.instructions.tag_ids,
            status: detection.instructions.auto_publish ? 'publish' : 'draft'
          },
          instructions: detection.instructions
        }
      };
    } else {
      return {
        json: {
          action: 'no_source_detected',
          message: 'No configured source found in content',
          post_data: {
            title: inputData.title,
            content: inputData.content,
            status: 'draft'
          }
        }
      };
    }
    break;
  
  // SCENARIO 2: Create post with full control
  case 'create_post':
    try {
      // Build post data
      const postData = {
        title: inputData.title,
        content: inputData.content,
        source_name: inputData.source_name,
        source_id: inputData.source_id,
        category_ids: inputData.category_ids,
        tag_ids: inputData.tag_ids,
        tag_names: inputData.tag_names,
        author_id: inputData.author_id || 1,
        status: inputData.status || 'draft',
        post_date: inputData.post_date,
        meta_fields: inputData.meta_fields
      };
      
      // Handle image - multiple options
      if (inputData.featured_image_url) {
        postData.featured_image_url = inputData.featured_image_url;
      } else if (inputData.featured_image_base64) {
        postData.featured_image_base64 = inputData.featured_image_base64;
      } else if (inputData.telegram_file_id) {
        postData.telegram_file_id = inputData.telegram_file_id;
      } else if (inputData.attachment_id) {
        postData.attachment_id = inputData.attachment_id;
      }
      
      const result = await createPost(postData);
      
      return {
        json: {
          action: 'post_created',
          success: result.success,
          post: result.post,
          source_used: result.source_used
        }
      };
    } catch (error) {
      return {
        json: {
          action: 'post_creation_failed',
          error: error.message
        }
      };
    }
    break;
  
  // SCENARIO 3: Validate content against source rules
  case 'validate_content':
    try {
      const validation = await validateContent(
        inputData.source_id,
        inputData.content,
        inputData.title
      );
      
      return {
        json: {
          action: 'validation_completed',
          valid: validation.valid,
          confidence: validation.confidence,
          ai_analysis: validation.ai_analysis,
          validation_details: validation.details
        }
      };
    } catch (error) {
      return {
        json: {
          action: 'validation_failed',
          error: error.message
        }
      };
    }
    break;
  
  // SCENARIO 4: Search existing posts
  case 'search_posts':
    const searchResults = await searchPosts({
      title: inputData.search_title,
      date_from: inputData.date_from,
      date_to: inputData.date_to,
      category_id: inputData.category_id,
      tag_ids: inputData.tag_ids,
      limit: inputData.limit || 20,
      status: inputData.status || 'any'
    });
    
    return {
      json: {
        action: 'search_completed',
        posts: searchResults.posts,
        total: searchResults.total,
        query: inputData
      }
    };
    break;
  
  // SCENARIO 5: Get all sources for mapping
  case 'get_sources':
    const mapping = await getAllSources();
    
    // Create easy lookup objects
    const sourcesByName = {};
    const sourcesBySlug = {};
    
    mapping.sources.forEach(source => {
      sourcesByName[source.source_name] = {
        id: source.source_id,
        category: source.category,
        tags: source.tags,
        auto_publish: source.auto_publish,
        has_ai_rules: source.has_detection_rules
      };
      sourcesBySlug[source.source_slug] = sourcesByName[source.source_name];
    });
    
    return {
      json: {
        action: 'sources_retrieved',
        sources: mapping.sources,
        sources_by_name: sourcesByName,
        sources_by_slug: sourcesBySlug,
        total: mapping.total
      }
    };
    break;
  
  // SCENARIO 6: Complete bulletin processing workflow
  case 'process_bulletin':
    try {
      // Step 1: Detect source
      const bulletinDetection = await detectSource(
        inputData.content, 
        inputData.title
      );
      
      // Step 2: Prepare post data
      const bulletinPostData = {
        title: inputData.title,
        content: inputData.content,
        author_id: inputData.author_id || 1
      };
      
      // Step 3: Apply source settings if detected
      if (bulletinDetection.detected) {
        bulletinPostData.source_id = bulletinDetection.source.id;
        bulletinPostData.status = bulletinDetection.source.auto_publish ? 'publish' : 'draft';
        
        // Log AI detection if used
        if (bulletinDetection.ai_analysis) {
          console.log('AI Detection Analysis:', bulletinDetection.ai_analysis);
        }
      } else {
        // Use provided source name if no detection
        if (inputData.default_source) {
          bulletinPostData.source_name = inputData.default_source;
        }
        bulletinPostData.status = inputData.status || 'draft';
      }
      
      // Step 4: Add image if provided
      if (inputData.image_url) {
        bulletinPostData.featured_image_url = inputData.image_url;
      } else if (inputData.image_base64) {
        bulletinPostData.featured_image_base64 = inputData.image_base64;
      } else if (inputData.telegram_file_id) {
        bulletinPostData.telegram_file_id = inputData.telegram_file_id;
      }
      
      // Step 5: Create the post
      const createdPost = await createPost(bulletinPostData);
      
      return {
        json: {
          action: 'bulletin_processed',
          success: createdPost.success,
          post: createdPost.post,
          source_detected: bulletinDetection.detected,
          detection_confidence: bulletinDetection.confidence,
          source_used: createdPost.source_used || bulletinDetection.source
        }
      };
    } catch (error) {
      return {
        json: {
          action: 'bulletin_processing_failed',
          error: error.message,
          input_data: inputData
        }
      };
    }
    break;
  
  // SCENARIO 7: Upload image independently
  case 'upload_image':
    try {
      const imageParams = {};
      
      if (inputData.image_url) {
        imageParams.url = inputData.image_url;
      } else if (inputData.image_base64) {
        imageParams.base64 = inputData.image_base64;
      } else if (inputData.telegram_file_id) {
        imageParams.telegram_file_id = inputData.telegram_file_id;
      }
      
      if (inputData.filename) {
        imageParams.filename = inputData.filename;
      }
      
      const uploadResult = await uploadImage(imageParams);
      
      return {
        json: {
          action: 'image_uploaded',
          success: uploadResult.success,
          attachment_id: uploadResult.attachment_id,
          url: uploadResult.url
        }
      };
    } catch (error) {
      return {
        json: {
          action: 'image_upload_failed',
          error: error.message
        }
      };
    }
    break;
  
  // DEFAULT: Show help and available actions
  default:
    return {
      json: {
        action: 'help',
        message: 'WP News Source Integration - Available Actions',
        available_actions: {
          'detect_and_prepare': 'Detect source using AI rules and prepare post data',
          'create_post': 'Create a post with full control',
          'validate_content': 'Validate content against source AI rules',
          'search_posts': 'Search existing posts',
          'get_sources': 'Get all configured sources',
          'process_bulletin': 'Complete bulletin processing workflow',
          'upload_image': 'Upload image independently'
        },
        features: {
          ai_detection: 'Sources can have JSON detection rules for AI-based matching',
          confidence_scoring: 'Detection includes confidence levels (0-1)',
          multi_image_support: 'Supports URL, base64, Telegram, and attachment IDs',
          auto_categorization: 'Automatic category and tag assignment based on source',
          webhook_support: 'Sources can trigger webhooks on detection'
        },
        example_requests: {
          detect_and_prepare: {
            action: 'detect_and_prepare',
            title: 'Press Release Title',
            content: 'Content mentioning source name or matching AI rules'
          },
          create_post: {
            action: 'create_post',
            title: 'Post title',
            content: '<p>HTML content</p>',
            source_name: 'Example Source Name',
            featured_image_url: 'https://example.com/image.jpg',
            status: 'publish'
          },
          validate_content: {
            action: 'validate_content',
            source_id: 123,
            title: 'Content to validate',
            content: 'Content that should match source rules'
          },
          search_posts: {
            action: 'search_posts',
            search_title: 'keyword',
            date_from: '2024-01-01',
            limit: 10
          }
        },
        configuration: {
          site_url: CONFIG.siteUrl,
          api_key_required: CONFIG.requireApiKey,
          api_key_configured: CONFIG.apiKey !== 'your-api-key-here'
        }
      }
    };
}