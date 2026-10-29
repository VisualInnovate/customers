<template>

    <Modal :label="$t('Media')" :isOpen=isOpen dialog-custom-class="max-w-[80vw] min-h-[90vh]" close-btn
        @close="isOpen = false">

        <div class="flex fixed w-full bg-white items-center gap-2 py-3 border-b">
            <button v-for="(media, index) in mediaTypes" :key="media.value"
                :class="selectedMedia === media.value ? 'bg-primary' : 'bg-primary-light'"
                class=" w-fit rounded-full  px-6 py-2 text-sm  border-transparent text-white shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 hover:opacity-70"
                @click="handleChangeMedia(media.value)" type="button">
                {{ media.name }}
            </button>
        </div>
        <div v-if="selectedMedia == 'image'" class="pt-24">
            <div class="py-2 grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6   ">
                <div class="w-[150px] h-[150px] overflow-hidden rounded-md" v-for="media in props.data.data">
                    <img class="w-full h-full object-cover" :key="media.id" :src="handleSrc(media.media.path)" alt="">
                </div>
            </div>
        </div>
        <div v-if="selectedMedia == 'video'" class="pt-24">
            <div class="py-2 grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6  ">
                <div v-for="media in props.data.data">

                    <video v-if="media && media.media?.type?.includes('video')" controls width="300"
                        class="w-[150px] h-[150px] bg-black">
                        <source :src="handleSrc(media?.media?.path)" type="video/mp4">
                        {{ $t('Your browser does not support the video element') }}
                    </video>
                </div>
            </div>


        </div>
        <div v-if="selectedMedia == 'audio'" class="pt-24">
            <div v-for="media in props.data.data">
                <audio v-if="media && media.media?.type?.includes('audio')" controls>
                    <source :src="handleSrc(media?.media?.path)">
                    {{ $t('Your browser does not support the audio element') }}
                </audio>
            </div>
        </div>

    </Modal>
</template>
<script setup>

    import { ref, watch, defineModel } from 'vue';
    import Modal from '@/Components/Modal.vue';
    const emits = defineEmits(['onClose', "onChange"]);
    const isOpen = defineModel()
    const props = defineProps(['data']);
    const selectedMedia = ref('image');
    const mediaTypes = ref([
        {
            name: 'Images',
            value: 'image'
        },
        {
            name: 'Videos',
            value: 'video'
        },
        {
            name: 'Audios',
            value: 'audio'
        }

    ])
    const handleChangeMedia = (media) => {
        props.data = ''
        selectedMedia.value = media;
        emits('onChange', media)
    }
    const handleSrc = (path) => {
        const baseUrl = "https://app.customercloudhq.com/media/";
        console.log(path, "paaath")
        if (path.includes(baseUrl)) return path
        else return baseUrl + path;

    }



</script>