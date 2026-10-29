<script setup>
import { ref } from 'vue';

const props = defineProps({
  clientId: {
    type: String,
    default: "1342252153600466" // يمكن تحديثه إذا لزم
  },
  redirectUri: {
    type: String,
    default: "https://app.customercloudhq.com/instagram/callback" // يمكن تحديثه إذا لزم
  }
});

const isSetupLoading = ref(false);
const popupWindow = ref(null);

function launchInstagramSignup() {
  // رابط تسجيل الدخول إلى إنستجرام
  const instagramLoginUrl = `https://www.instagram.com/accounts/login/?force_authentication=1&next=${encodeURIComponent(
    `/oauth/authorize?enable_fb_login=0&client_id=${props.clientId}&redirect_uri=${props.redirectUri}&response_type=code&scope=instagram_business_basic,instagram_business_manage_messages,instagram_business_manage_comments,instagram_business_content_publish`
  )}`;

  // إعداد نافذة منبثقة
  const width = 600;
  const height = 700;
  const left = window.screenX + (window.innerWidth - width) / 2;
  const top = window.screenY + (window.innerHeight - height) / 2;

  isSetupLoading.value = true;

  // فتح النافذة
  popupWindow.value = window.open(
    instagramLoginUrl,
    "_blank",
    `width=${width},height=${height},top=${top},left=${left},resizable=yes,scrollbars=yes`
  );

  // استماع لأحداث النافذة
  const checkPopupClosed = setInterval(() => {
    if (popupWindow.value && popupWindow.value.closed) {
      clearInterval(checkPopupClosed);
      isSetupLoading.value = false;
      popupWindow.value = null;
    }
  }, 500);
}
</script>

<template>
  <div class="bg-white rounded-[0.5rem] shadow-md text-sm space-y-3 flex flex-col">
    <div class="flex items-center gap-x-3 pb-2 bg-slate-50 px-4 pt-4 rounded-tr-[0.5rem] rounded-tl-[0.5rem]">
      <div>
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="32" height="32"><path fill="url(#grad1)" d="M12 2.163c3.213 0 3.589.012 4.85.07 1.156.057 2.19.228 3.19.746a5.896 5.896 0 0 1 2.139 2.139c.518 1 .688 2.034.746 3.19.058 1.26.07 1.637.07 4.85s-.012 3.589-.07 4.85c-.057 1.156-.228 2.19-.746 3.19a5.896 5.896 0 0 1-2.139 2.139c-1 .518-2.034.688-3.19.746-1.26.058-1.637.07-4.85.07s-3.589-.012-4.85-.07c-1.156-.058-2.19-.228-3.19-.746a5.896 5.896 0 0 1-2.139-2.139c-.518-1-.688-2.034-.746-3.19-.058-1.26-.07-1.637-.07-4.85s.012-3.589.07-4.85c.057-1.156.228-2.19.746-3.19a5.896 5.896 0 0 1 2.139-2.139c1-.518 2.034-.688 3.19-.746 1.26-.058 1.637-.07 4.85-.07zM12 0c-3.292 0-3.69.013-4.974.072-1.184.059-2.264.231-3.182.689a7.866 7.866 0 0 0-2.868 2.868c-.458.918-.63 1.998-.689 3.182C.013 8.31 0 8.708 0 12c0 3.292.013 3.69.072 4.974.059 1.184.231 2.264.689 3.182a7.866 7.866 0 0 0 2.868 2.868c.918.458 1.998.63 3.182.689 1.284.059 1.682.072 4.974.072 3.292 0 3.69-.013 4.974-.072 1.184-.059 2.264-.231 3.182-.689a7.866 7.866 0 0 0 2.868-2.868c.458-.918.63-1.998.689-3.182.059-1.284.072-1.682.072-4.974 0-3.292-.013-3.69-.072-4.974-.059-1.184-.231-2.264-.689-3.182a7.866 7.866 0 0 0-2.868-2.868c-.918-.458-1.998-.63-3.182-.689-1.284-.059-1.682-.072-4.974-.072zm0 5.838a6.146 6.146 0 1 0 0 12.292 6.146 6.146 0 0 0 0-12.292zm0 9.553a3.407 3.407 0 1 1 0-6.813 3.407 3.407 0 0 1 0 6.813zm6.406-9.56a.981.981 0 1 1-.001 1.963.981.981 0 0 1 .001-1.963z"/><defs><linearGradient id="grad1" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" style="stop-color:#F58529;stop-opacity:1" /> <stop offset="50%" style="stop-color:#F77737;stop-opacity:1" /><stop offset="100%" style="stop-color:#D83A8C;stop-opacity:1" /></linearGradient></defs></svg>
      </div>
      <div class="text-[16px]">Instagram</div>
    </div>
    <div class="text-slate-500 border-b px-4 text-xs pt-2 pb-4">
      Share your moments and connect with your followers directly from your platform.
    </div>
    <div class="flex justify-end items-center px-4 pb-4 text-xs">
      <button @click="launchInstagramSignup" 
              class="rounded-full border-2 px-10 py-2 disabled:opacity-50 hover:border-secondary hover:bg-secondary hover:text-white text-secondary">
        Connect
      </button>
    </div>
  </div>
</template>
