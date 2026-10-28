import { ref } from 'vue';
import axios from 'axios';
import { cacheManager, optimizePayload } from '../lib/performance';

/**
 * Composable for optimized API calls with caching and payload optimization
 */
export function useOptimizedApi() {
  const loading = ref(false);
  const error = ref(null);

  /**
   * Make a cached GET request
   * @param {string} url - The API endpoint
   * @param {number} cacheTTL - Cache time-to-live in minutes (default: 5)
   */
  const getCached = async (url, cacheTTL = 5) => {
    loading.value = true;
    error.value = null;

    try {
      const response = await axios.get(url, {
        cache: true,
        cacheTTL
      });
      return response.data;
    } catch (err) {
      error.value = err;
      throw err;
    } finally {
      loading.value = false;
    }
  };

  /**
   * Make a POST request with optimized payload
   * @param {string} url - The API endpoint
   * @param {object} data - The data to send
   */
  const postOptimized = async (url, data) => {
    loading.value = true;
    error.value = null;

    try {
      const optimized = optimizePayload(data);
      const response = await axios.post(url, optimized);
      return response.data;
    } catch (err) {
      error.value = err;
      throw err;
    } finally {
      loading.value = false;
    }
  };

  /**
   * Make a PUT request with optimized payload
   * @param {string} url - The API endpoint
   * @param {object} data - The data to send
   */
  const putOptimized = async (url, data) => {
    loading.value = true;
    error.value = null;

    try {
      const optimized = optimizePayload(data);
      const response = await axios.put(url, optimized);
      return response.data;
    } catch (err) {
      error.value = err;
      throw err;
    } finally {
      loading.value = false;
    }
  };

  /**
   * Clear cache for specific URL or all cache
   * @param {string} url - Optional URL to clear, or all if not provided
   */
  const clearCache = (url = null) => {
    if (url) {
      cacheManager.clear(url);
    } else {
      cacheManager.clear();
    }
  };

  return {
    loading,
    error,
    getCached,
    postOptimized,
    putOptimized,
    clearCache
  };
}

export default useOptimizedApi;
