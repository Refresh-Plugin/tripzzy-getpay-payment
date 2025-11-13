if (!window.TripzzyGetpaySafeStorage) {
  window.TripzzyGetpaySafeStorage = {
    available: (() => {
      try {
        const testKey = "__storage_test__";
        localStorage.setItem(testKey, testKey);
        localStorage.removeItem(testKey);
        return true;
      } catch (e) {
        return false;
      }
    })(),

    set(key, value) {
      if (this.available) {
        localStorage.setItem(key, JSON.stringify(value));
      } else {
        console.warn("localStorage not available — data not saved");
      }
    },

    get(key) {
      if (!this.available) return null;
      try {
        return JSON.parse(localStorage.getItem(key));
      } catch {
        return null;
      }
    },

    remove(key) {
      if (this.available) {
        localStorage.removeItem(key);
      }
    },
  };
}
