<template>
  <AppLayout v-slot:default="slotProps">
    </AppLayout>
  </template>
  <script setup>
   import AppLayout from "./../Layout/App.vue";
   import axios from 'axios';
   import { defineEmits, ref, onMounted, watch } from 'vue';
   onMounted(() => {
    console.log('saccsa')

    axios.get('livechat/show')
      const echo = getEchoInstance(
          props.pusherSettings['pusher_app_key'],
          props.pusherSettings['pusher_app_cluster']
      );

      echo.channel('chats.ch' + props.organizationId).listen('NewChatEvent', (event) => {
          updateSidePanel(event.chat);
      });
      echo.channel('status.ch' + props.organizationId).listen('NewStatusEvent', (event) => {
          updateStatus(event);
      });

      scrollToBottom();
  });
</script>