// useAlertModal.js
import { ref } from 'vue';

export function useAlertModal() {
  const isOpenAlert = ref(false);
  const selectedItem = ref(null);

  function openAlert(key) {
    isOpenAlert.value = true;
    selectedItem.value = key;
  }

  async function confirmAlert(deleteAction) {
    try {
      isOpenAlert.value = false;
      await deleteAction(selectedItem.value);
    } catch (error) {
      // Handle error
    }
    selectedItem.value = null;
  }

  return {
    isOpenAlert,
    selectedItem,
    openAlert,
    confirmAlert,
  };
}
