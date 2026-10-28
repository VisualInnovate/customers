import axios from "axios";
import { performanceMonitor, cacheManager } from './lib/performance';

axios.defaults.headers.common['Access-Control-Allowffff-Origin'] = '*'

// Enable compression support
axios.defaults.headers.common['Accept-Encoding'] = 'gzip, deflate';

axios.interceptors.request.use((config) => {
  // Add performance tracking
  config.metadata = { startTime: performance.now() };

  // Check cache for GET requests
  if (config.method === 'get' && config.cache) {
    const cachedData = cacheManager.get(config.url);
    if (cachedData) {
      console.log('✅ Cache hit:', config.url);
      return Promise.reject({
        __cached: true,
        data: cachedData,
        config
      });
    }
  }

  config.headers.Accept = "application/json";
  return config;
});

axios.interceptors.response.use(
  (response) => {
    // Track performance metrics
    if (response.config.metadata) {
      performanceMonitor.measureApiCall(
        response.config.url,
        response.config.metadata.startTime,
        response
      );
    }

    // Cache GET responses if caching is enabled
    if (response.config.method === 'get' && response.config.cache) {
      const ttl = response.config.cacheTTL || 5; // Default 5 minutes
      cacheManager.set(response.config.url, response.data, ttl);
    }

    return response;
  },
  (error) => {
    // Handle cached responses
    if (error.__cached) {
      return Promise.resolve({
        data: error.data,
        status: 200,
        config: error.config,
        cached: true
      });
    }

    if (error.response?.status === 401) {
      localStorage.removeItem('token')
      router.push({ name: 'login' })
    }
    return Promise.reject(error)
  }
)

export default axios