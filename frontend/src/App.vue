<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { WifiOff } from '@lucide/vue'

const isOnline = ref(navigator.onLine)

const updateOnlineStatus = () => {
  isOnline.value = navigator.onLine
}

onMounted(() => {
  window.addEventListener('online', updateOnlineStatus)
  window.addEventListener('offline', updateOnlineStatus)
})

onUnmounted(() => {
  window.removeEventListener('online', updateOnlineStatus)
  window.removeEventListener('offline', updateOnlineStatus)
})
</script>

<template>
  <div>
    <transition name="banner-slide">
      <div v-if="!isOnline" class="global-offline-banner" role="alert">
        <WifiOff :size="16" class="offline-icon" />
        <span>Koneksi internet terputus. Pastikan perangkat Anda terhubung ke jaringan internet.</span>
      </div>
    </transition>
    <router-view />
  </div>
</template>

<style scoped>
.global-offline-banner {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 99999;
  background: #dc2626;
  color: #ffffff;
  padding: 10px 16px;
  font-size: 13.5px;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
  font-family: var(--font-sans);
}

.offline-icon {
  flex-shrink: 0;
}

.banner-slide-enter-active,
.banner-slide-leave-active {
  transition: transform 0.3s ease, opacity 0.3s ease;
}

.banner-slide-enter-from,
.banner-slide-leave-to {
  transform: translateY(-100%);
  opacity: 0;
}
</style>
