// Performance monitoring utilities for frontend
export const performanceMonitor = {
  /**
   * Measure API response time and size
   */
  measureApiCall: (url, startTime, response) => {
    const endTime = performance.now();
    const duration = endTime - startTime;
    const size = JSON.stringify(response.data).length;
    const compressed = response.headers['content-encoding'];
    
    if (import.meta.env.DEV) {
      console.log(`🚀 API Call: ${url}`);
      console.log(`   Duration: ${duration.toFixed(2)}ms`);
      console.log(`   Size: ${(size / 1024).toFixed(2)}KB`);
      console.log(`   Compressed: ${compressed || 'No'}`);
    }
    
    return { duration, size, compressed };
  },

  /**
   * Track page load performance
   */
  trackPageLoad: () => {
    if (typeof window !== 'undefined' && window.performance) {
      const perfData = window.performance.timing;
      const pageLoadTime = perfData.loadEventEnd - perfData.navigationStart;
      const connectTime = perfData.responseEnd - perfData.requestStart;
      
      if (import.meta.env.DEV) {
        console.log(`📊 Page Performance:`);
        console.log(`   Total Load: ${pageLoadTime}ms`);
        console.log(`   Server Response: ${connectTime}ms`);
      }
      
      return { pageLoadTime, connectTime };
    }
  }
};

/**
 * Optimize large payloads before sending
 */
export const optimizePayload = (data) => {
  // Remove null/undefined values to reduce payload size
  const removeEmpty = (obj) => {
    return Object.entries(obj).reduce((acc, [key, value]) => {
      if (value !== null && value !== undefined && value !== '') {
        acc[key] = typeof value === 'object' && !Array.isArray(value) 
          ? removeEmpty(value) 
          : value;
      }
      return acc;
    }, {});
  };
  
  return removeEmpty(data);
};

/**
 * Cache frequently accessed data in localStorage with TTL
 */
export const cacheManager = {
  set: (key, data, ttlMinutes = 5) => {
    const item = {
      data,
      expires: Date.now() + (ttlMinutes * 60 * 1000)
    };
    localStorage.setItem(`cache_${key}`, JSON.stringify(item));
  },
  
  get: (key) => {
    const itemStr = localStorage.getItem(`cache_${key}`);
    if (!itemStr) return null;
    
    const item = JSON.parse(itemStr);
    if (Date.now() > item.expires) {
      localStorage.removeItem(`cache_${key}`);
      return null;
    }
    
    return item.data;
  },
  
  clear: (key) => {
    if (key) {
      localStorage.removeItem(`cache_${key}`);
    } else {
      // Clear all cache items
      Object.keys(localStorage)
        .filter(k => k.startsWith('cache_'))
        .forEach(k => localStorage.removeItem(k));
    }
  }
};

export default {
  performanceMonitor,
  optimizePayload,
  cacheManager
};

