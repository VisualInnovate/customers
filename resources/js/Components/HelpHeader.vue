<template>
    <div class="flex md:flex-row flex-col justify-between ">
        <h1 class="md:text-2xl text-1xl  text-[#121212] opacity-1 ">CustomerCloud Help Center</h1>
        <div class="flex justify-center gap-4 md:mt-0 mt-5 ">
        <div class="md:bg-white flex items-center border border-primary md:border-none md:shadow-sm h-12 md:h-10 md:w-80 rounded-[0.5rem] text-xl md:text-sm">
            <span class="pl-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 15l6 6m-11-4a7 7 0 1 1 0-14a7 7 0 0 1 0 14Z"/></svg>
            </span>
            <input type="text" placeholder="Search" class="outline-none px-4 w-full bg-inherit">
        </div>
        <div class=" flex justify-center">
            <a  href="" class="rounded-md bg-primary px-5 md:px-3 py-2 text-[16px] text-white shadow-sm hover:bg-primaryLight focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"> Contact Us</a>
        </div>
        </div>
    </div>
    <div class="mt-5 bg-primary flex flex-col justify-center gap-16">
        <div class=" flex flex-col pt-16 px-4 md:px-32 text-white ">
        <h1 class=" text-2xl md:text-4xl">Getting started</h1>
        <p class="" >Everything you need to know to get started in CustomerCloud </p>
        </div>
        <div class="menu px-2  md:px-32">
        <ul class="flex md:flex-row flex-col gap-4 ">
            <li class="selected text-primary  text-sm md:text-lg bg-white inline-block  rounded-tl-md rounded-tr-md px-4  py-1 1 md:block relative">
            <a href="#">Get started</a>
            <span class="absolute right-2 top-1/2 transform -translate-y-1/2 cursor-pointer md:hidden">
                ▼
            </span>
            </li>
            <li class=" text-lg text-white  inline-block  rounded-tl-md rounded-tr-md px-4  py-1">
            <a href="#">Get started</a>
            </li>
            <li class=" text-lg text-white inline-block  rounded-tl-md rounded-tr-md px-4  py-1">
            <a href="#">Get started</a>
            </li>
            <li class=" text-lg text-white  inline-block  rounded-tl-md rounded-tr-md px-4  py-1">
            <a href="#" >Get started</a>
            </li>
        </ul>
        
        </div>
    </div>
</template>

<script setup>

</script>

<style>

</style>