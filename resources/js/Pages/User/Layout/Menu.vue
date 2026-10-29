<template>
    <div v-if="getValueByKey('logo') === null" class="flex items-center justify-between px-5 pt-5 h-20 mb-4">
        <h2 v-if="!menuIconsOnly" class="ml-2 text-2xl">{{ getValueByKey('company_name') }}</h2>
        <LangToggle v-if="!menuIconsOnly" class="text-black" :languages="languages" :currentLanguage="currentLanguage" />
        <span v-if="isSidebarOpen === true" @click="closeSidebar()">
            <svg xmlns="http://www.w3.org/2000/svg" width="2em" height="2em" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="2"><path d="M5 5L12 5L19 5"><animate fill="freeze" attributeName="d" dur="0.4s" values="M5 5L12 5L19 5;M5 5L12 12L19 5"/></path><path d="M5 12H19"><animate fill="freeze" attributeName="d" dur="0.4s" values="M5 12H19;M12 12H12"/></path><path d="M5 19L12 19L19 19"><animate fill="freeze" attributeName="d" dur="0.4s" values="M5 19L12 19L19 19;M5 19L12 12L19 19"/></path></g></svg>
        </span>
    </div>
    <div v-else class="flex items-center justify-between px-5 pt-5 h-20 mb-1">
        <Link href="/dashboard">
            <img :src="'/media/' + getValueByKey('logo')" alt="{{ getValueByKey('company_name') }}" class="w-32 object-contain h-full ps-2">
        </Link>
        <span v-if="isSidebarOpen === true" @click="closeSidebar()">
            <svg xmlns="http://www.w3.org/2000/svg" width="2em" height="2em" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="2"><path d="M5 5L12 5L19 5"><animate fill="freeze" attributeName="d" dur="0.4s" values="M5 5L12 5L19 5;M5 5L12 12L19 5"/></path><path d="M5 12H19"><animate fill="freeze" attributeName="d" dur="0.4s" values="M5 12H19;M12 12H12"/></path><path d="M5 19L12 19L19 19"><animate fill="freeze" attributeName="d" dur="0.4s" values="M5 19L12 19L19 19;M5 19L12 12L19 19"/></path></g></svg>
        </span>
        <LangToggle class="text-black" :languages="languages" :currentLanguage="currentLanguage" />
    </div>
    <div class="flex-grow space-y-3 px-2 overflow-y-scroll">
        <div class="flex-1">
            <ul class="pt-2 space-y-1 text-sm mb-2">
                <li v-if="!isAgent" class="hover:bg-slate-50 hover:text-black rounded-[5px] px-2 truncate" :class="$page.url.startsWith('/dashboard') ? 'bg-slate-50 text-black' : ''">
                    <Link rel="noopener noreferrer" href="/dashboard" class="flex items-center p-2 space-x-3 rounded-md">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 11.452V16.8c0 1.12 0 1.68.218 2.109c.192.376.497.682.874.873c.427.218.987.218 2.105.218h9.606c1.118 0 1.677 0 2.104-.218a2 2 0 0 0 .875-.873c.218-.428.218-.987.218-2.105v-5.352c0-.534 0-.801-.065-1.05a1.998 1.998 0 0 0-.28-.617c-.145-.213-.345-.39-.748-.741l-4.8-4.2c-.746-.653-1.12-.98-1.54-1.104c-.37-.11-.764-.11-1.135 0c-.42.124-.792.45-1.538 1.102L5.093 9.044c-.402.352-.603.528-.747.74a2 2 0 0 0-.281.618C4 10.65 4 10.918 4 11.452Z"/></svg>
                        <span :class="menuIconsOnly ? 'hidden' : ''">{{ $t('Dashboard') }}  </span>
                    </Link>
                </li>
                <li class="hover:bg-slate-50 hover:text-black rounded-[5px] px-2 truncate" :class="$page.url.startsWith('/chats') ? 'bg-slate-50 text-black' : ''">
                    <Link rel="noopener noreferrer" href="/chats" class="flex items-center justify-between p-2 space-x-3 rounded-md">
                        <div class="flex items-center space-x-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5.6 19.92l1.524-1.219l.01-.008c.318-.255.479-.383.658-.474c.16-.082.331-.142.508-.178c.199-.041.406-.041.822-.041h8.681c1.118 0 1.678 0 2.105-.218a2 2 0 0 0 .874-.874C21 16.48 21 15.92 21 14.804V7.197c0-1.118 0-1.678-.218-2.105a2.001 2.001 0 0 0-.875-.874C19.48 4 18.92 4 17.8 4H6.2c-1.12 0-1.68 0-2.108.218a1.999 1.999 0 0 0-.874.874C3 5.52 3 6.08 3 7.2v11.471c0 1.066 0 1.599.218 1.872a1 1 0 0 0 .783.377c.35 0 .766-.334 1.599-1Z"/></svg>
                            <span :class="menuIconsOnly ? 'hidden' : ''">{{ $t('Chats') }}</span>
                        </div>
                        <span v-if="parseInt(unreadMessages) > 0" class="bg-[#ffe5b4] px-2 text-[11px] rounded-md">{{ unreadMessages }}</span>
                    </Link>
                </li>
                <li class="hover:bg-slate-50 hover:text-black rounded-[5px] px-2 truncate" :class="$page.url.startsWith('/contact') ? 'bg-slate-50 text-black' : ''">
                    <Link rel="noopener noreferrer" href="/contacts" class="flex items-center p-2 space-x-3 rounded-md">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4h-.8c-1.12 0-1.68 0-2.108.218a1.999 1.999 0 0 0-.874.874C4 5.52 4 6.08 4 7.2v9.6c0 1.12 0 1.68.218 2.108a2 2 0 0 0 .874.874c.427.218.987.218 2.105.218H8M8 4h8.8c1.12 0 1.68 0 2.107.218c.377.192.683.497.875.874c.218.427.218.987.218 2.105v9.607c0 1.118 0 1.677-.218 2.104a2.002 2.002 0 0 1-.875.874c-.427.218-.986.218-2.104.218H8M8 4v16m4-9h4m-4-3h4"/></svg>
                        <span :class="menuIconsOnly ? 'hidden' : ''">{{ $t('Contacts') }}</span>
                    </Link>
                </li>
                <li class="hover:bg-slate-50 hover:text-black rounded-[5px] px-2 truncate" :class="$page.url.startsWith('/tickets/index') ? 'bg-slate-50 text-black' : ''">
                    <Link rel="noopener noreferrer" href="/tickets/index" class="flex items-center p-2 space-x-3 rounded-md">
                        <svg fill="#000000" height="24" width="24" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 512.001 512.001" xml:space="preserve"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="25.60005"> <g> <g> <path d="M378.473,104.189l-28.348-19.626c-3.791-2.623-8.991-1.678-11.616,2.112c-2.625,3.791-1.678,8.991,2.112,11.616 l28.348,19.626c3.754,2.599,9.018,1.638,11.615-2.111C383.208,112.013,382.263,106.813,378.473,104.189z"></path> </g> </g> <g> <g> <path d="M435.17,143.439l-28.348-19.625c-3.791-2.625-8.99-1.679-11.616,2.111c-2.626,3.791-1.681,8.991,2.11,11.616 c0,0,28.292,19.586,28.348,19.625c3.611,2.5,8.978,1.699,11.616-2.111C439.905,151.265,438.96,146.064,435.17,143.439z"></path> </g> </g> <g> <g> <path d="M167.004,134.473l-33.566-7.877c-4.487-1.052-8.98,1.731-10.035,6.22c-1.053,4.489,1.732,8.981,6.22,10.035l33.566,7.877 c0.642,0.15,1.282,0.223,1.916,0.223c3.787,0,7.216-2.597,8.12-6.443C174.277,140.018,171.492,135.525,167.004,134.473z"></path> </g> </g> <g> <g> <path d="M234.138,150.227l-33.566-7.877c-4.488-1.052-8.98,1.731-10.035,6.22c-1.053,4.489,1.732,8.981,6.22,10.034l33.566,7.877 c0.642,0.15,1.282,0.223,1.916,0.223c3.787,0,7.216-2.596,8.12-6.442C241.411,155.772,238.626,151.28,234.138,150.227z"></path> </g> </g> <g> <g> <path d="M247.23,189.779l-76.055-17.847c-4.487-1.052-8.98,1.731-10.035,6.22c-1.053,4.489,1.732,8.981,6.22,10.034l67.928,15.94 l-54.804,233.553L53.627,407.911l54.803-233.553l25.824,6.059c4.489,1.053,8.98-1.732,10.034-6.22 c1.054-4.488-1.732-8.981-6.221-10.034l-33.95-7.966c-4.478-1.052-8.979,1.731-10.034,6.22L35.465,412.224 c-1.053,4.489,1.732,8.981,6.22,10.034l143.11,33.582c0.63,0.148,1.271,0.22,1.908,0.22c1.542,0,3.066-0.427,4.398-1.252 c1.882-1.167,3.223-3.033,3.73-5.188l58.619-249.807C254.502,195.324,251.717,190.832,247.23,189.779z"></path> </g> </g> <g> <g> <path d="M204.405,283.961c-1.401-3.335-4.786-5.386-8.394-5.084l-31.233,2.619l-12.142-28.894c-1.4-3.335-4.794-5.387-8.394-5.084 c-3.603,0.302-6.604,2.891-7.43,6.412l-7.16,30.513l-31.233,2.62c-3.603,0.302-6.604,2.891-7.43,6.411 c-0.826,3.521,0.709,7.174,3.802,9.047l26.807,16.238l-7.16,30.514c-0.826,3.521,0.709,7.174,3.802,9.047 c1.337,0.81,2.834,1.208,4.323,1.208c1.958,0,3.902-0.687,5.456-2.028l23.727-20.477l26.808,16.239 c3.091,1.873,7.04,1.543,9.779-0.82c2.738-2.363,3.642-6.22,2.242-9.553l-12.142-28.895l23.728-20.477 C204.903,291.152,205.806,287.295,204.405,283.961z M160.694,314.869l4.539,10.801c0,0-10.02-6.069-10.022-6.071 c-6.933-4.2-13.896,4.373-18.647,8.474l2.676-11.404c0.826-3.521-0.709-7.174-3.802-9.047l-10.02-6.071 c0,0,11.658-0.977,11.674-0.978c8.105-0.68,8.676-11.727,10.105-17.817c0,0,4.528,10.775,4.538,10.799 c3.159,7.516,13.816,4.63,20.068,4.106C166.988,301.819,157.455,307.161,160.694,314.869z"></path> </g> </g> <g> <g> <path d="M508.405,127.564c-7.115-4.925-9.523-14.443-5.604-22.14c1.896-3.722,0.747-8.273-2.687-10.651l-42.697-29.559 c-3.436-2.377-8.097-1.851-10.917,1.233c-3.271,3.583-7.951,5.638-12.836,5.638c-3.531,0-6.939-1.067-9.858-3.09 c-7.115-4.925-9.523-14.443-5.604-22.14c1.896-3.722,0.747-8.273-2.687-10.651L372.821,6.645 c-3.436-2.377-8.099-1.851-10.916,1.233c-3.272,3.583-7.953,5.636-12.837,5.638c-3.531,0-6.939-1.067-9.859-3.09 c-3.788-2.623-8.99-1.679-11.615,2.111l-48.265,69.714c-0.778-0.499-1.647-0.88-2.591-1.102l-50.557-11.862 c-4.066-0.955-8.213,1.245-9.705,5.147c-3.051,7.981-11.885,12.632-20.184,10.684c-8.422-1.977-14.151-9.95-13.324-18.548 c0.4-4.158-2.336-7.972-6.403-8.927l-50.556-11.863c-4.065-0.955-8.212,1.245-9.705,5.147 c-3.05,7.972-11.892,12.631-20.184,10.685c-4.483-1.053-8.979,1.732-10.034,6.22c0,0-12.5,53.268-12.511,53.316L0.223,433.747 c-1.053,4.489,1.732,8.981,6.22,10.034c8.424,1.977,14.152,9.951,13.325,18.548c-0.4,4.158,2.336,7.972,6.402,8.927l50.555,11.863 c4.074,0.955,8.213-1.244,9.705-5.146c7.029-18.385,35.387-11.661,33.51,7.863c-0.4,4.158,2.336,7.972,6.403,8.927l50.555,11.863 c4.017,0.939,8.23-1.289,9.705-5.148c2.549-6.668,9.057-11.149,16.195-11.149c3.682,0,6.845,1.572,10.295-0.565 c1.881-1.167,3.223-3.033,3.73-5.189l10.978-46.782c0,0,23.539,16.296,23.547,16.302c3.319,2.298,8.107,1.841,10.917-1.233 c3.271-3.583,7.951-5.636,12.835-5.636c3.531,0,6.94,1.069,9.86,3.09c3.791,2.623,8.99,1.677,11.615-2.113L510.515,139.18 C513.14,135.39,512.195,130.188,508.405,127.564z M226.923,368.429c0,0-12.581,53.615-12.582,53.619l-12.106,51.591 c-11.276,0.189-21.692,6.063-27.813,15.258l-37.974-8.911c-1.744-13.841-11.972-25.654-26.021-28.95 c-13.723-3.22-28.369,2.629-36.177,14.356l-37.973-8.911c-1.398-11.089-8.241-20.878-18.133-26.026l69.784-297.387l8.128,1.907 c0.641,0.15,1.281,0.223,1.914,0.223c3.787,0,7.216-2.597,8.12-6.443c1.053-4.489-1.732-8.981-6.221-10.034l-8.126-1.907 l8.927-38.043c11.276-0.189,21.691-6.063,27.813-15.258l37.974,8.911c1.744,13.841,11.971,25.654,26.02,28.952 c13.721,3.219,28.372-2.63,36.177-14.355l37.974,8.91c0.108,0.027,0.349,2.19,0.397,2.429c0.496,2.459,1.294,4.936,2.34,7.25 c3.139,6.953,8.614,12.832,15.395,16.351l-8.929,38.052l-8.128-1.907c-4.479-1.052-8.979,1.731-10.034,6.22 c-1.053,4.489,1.732,8.981,6.221,10.034l8.126,1.907l-13.276,56.578c0,0-1.45,6.18-1.451,6.184l-9.748,41.54 C247.54,280.566,226.923,368.426,226.923,368.429z M470.382,167.82l-6.864-4.753c-3.789-2.625-8.991-1.678-11.615,2.111 c-2.625,3.791-1.68,8.991,2.112,11.616l6.863,4.752L287.009,432.681c-3.784-1.42-7.806-2.154-11.913-2.154 c-7.047,0-13.882,2.192-19.562,6.16l-23.637-16.364l8.483-36.15l36.92,25.56c3.75,2.594,9.019,1.638,11.615-2.112l107.353-155.061 c2.625-3.791,1.68-8.991-2.11-11.616c-3.791-2.625-8.991-1.679-11.616,2.111L279.939,391.254l-35.461-24.551l16.277-69.365 l29.321-10.271l18.995,24.93c1.611,2.114,4.087,3.289,6.642,3.289c0.922,0,1.854-0.152,2.759-0.47 c3.413-1.195,5.669-4.452,5.586-8.069l-0.715-31.333l29.58-10.361c3.413-1.195,5.669-4.452,5.586-8.068 c-0.082-3.615-2.483-6.767-5.948-7.806l-30.021-9.005l-0.715-31.335c-0.082-3.616-2.483-6.767-5.948-7.806 c-3.465-1.039-7.203,0.272-9.262,3.245l-17.838,25.768l-11.43-3.428l21.491-91.583l10.519-15.194l107.133,74.171l-14.526,20.984 c-2.625,3.791-1.68,8.991,2.11,11.616c3.791,2.625,8.99,1.678,11.616-2.111c0,0,19.213-27.752,19.278-27.845 c2.582-3.73,1.603-9.044-2.11-11.616c0,0-120.644-83.524-120.861-83.675c-3.752-2.598-9.023-1.635-11.616,2.111l-3.623,5.234 c-0.1-0.027-0.196-0.062-0.297-0.086c-4.116-0.969-7.77-3.448-10.204-6.902c-0.018-0.025-0.033-0.052-0.051-0.078l19.201-27.734 c0,0,6.862,4.75,6.862,4.752c3.646,2.524,8.99,1.68,11.616-2.112c2.625-3.79,1.68-8.991-2.11-11.616l-6.863-4.752l22.241-32.125 c3.784,1.42,7.807,2.154,11.914,2.154c7.047,0,13.881-2.192,19.561-6.161l32.065,22.2c-3.446,13.518,1.741,28.256,13.607,36.471 c11.601,8.033,27.364,7.974,38.927-0.102l32.065,22.2c-2.76,10.83,0.021,22.446,7.338,30.86L470.382,167.82z M306.783,281.445 c0,0-7.098-9.314-7.101-9.32c-2.164-2.839-6.034-4-9.4-2.819l-11.056,3.872c3.639-5.257,11.529-12.768,6.445-19.443l-7.1-9.32 c0,0,11.211,3.363,11.223,3.366c7.824,2.346,12.336-7.687,15.929-12.876c0,0,0.267,11.701,0.267,11.712 c0.186,8.144,11.163,9.37,17.169,11.172l-11.057,3.872c-3.413,1.195-5.669,4.453-5.586,8.07L306.783,281.445z"></path> </g> </g> </g><g id="SVGRepo_iconCarrier"> <g> <g> <path d="M378.473,104.189l-28.348-19.626c-3.791-2.623-8.991-1.678-11.616,2.112c-2.625,3.791-1.678,8.991,2.112,11.616 l28.348,19.626c3.754,2.599,9.018,1.638,11.615-2.111C383.208,112.013,382.263,106.813,378.473,104.189z"></path> </g> </g> <g> <g> <path d="M435.17,143.439l-28.348-19.625c-3.791-2.625-8.99-1.679-11.616,2.111c-2.626,3.791-1.681,8.991,2.11,11.616 c0,0,28.292,19.586,28.348,19.625c3.611,2.5,8.978,1.699,11.616-2.111C439.905,151.265,438.96,146.064,435.17,143.439z"></path> </g> </g> <g> <g> <path d="M167.004,134.473l-33.566-7.877c-4.487-1.052-8.98,1.731-10.035,6.22c-1.053,4.489,1.732,8.981,6.22,10.035l33.566,7.877 c0.642,0.15,1.282,0.223,1.916,0.223c3.787,0,7.216-2.597,8.12-6.443C174.277,140.018,171.492,135.525,167.004,134.473z"></path> </g> </g> <g> <g> <path d="M234.138,150.227l-33.566-7.877c-4.488-1.052-8.98,1.731-10.035,6.22c-1.053,4.489,1.732,8.981,6.22,10.034l33.566,7.877 c0.642,0.15,1.282,0.223,1.916,0.223c3.787,0,7.216-2.596,8.12-6.442C241.411,155.772,238.626,151.28,234.138,150.227z"></path> </g> </g> <g> <g> <path d="M247.23,189.779l-76.055-17.847c-4.487-1.052-8.98,1.731-10.035,6.22c-1.053,4.489,1.732,8.981,6.22,10.034l67.928,15.94 l-54.804,233.553L53.627,407.911l54.803-233.553l25.824,6.059c4.489,1.053,8.98-1.732,10.034-6.22 c1.054-4.488-1.732-8.981-6.221-10.034l-33.95-7.966c-4.478-1.052-8.979,1.731-10.034,6.22L35.465,412.224 c-1.053,4.489,1.732,8.981,6.22,10.034l143.11,33.582c0.63,0.148,1.271,0.22,1.908,0.22c1.542,0,3.066-0.427,4.398-1.252 c1.882-1.167,3.223-3.033,3.73-5.188l58.619-249.807C254.502,195.324,251.717,190.832,247.23,189.779z"></path> </g> </g> <g> <g> <path d="M204.405,283.961c-1.401-3.335-4.786-5.386-8.394-5.084l-31.233,2.619l-12.142-28.894c-1.4-3.335-4.794-5.387-8.394-5.084 c-3.603,0.302-6.604,2.891-7.43,6.412l-7.16,30.513l-31.233,2.62c-3.603,0.302-6.604,2.891-7.43,6.411 c-0.826,3.521,0.709,7.174,3.802,9.047l26.807,16.238l-7.16,30.514c-0.826,3.521,0.709,7.174,3.802,9.047 c1.337,0.81,2.834,1.208,4.323,1.208c1.958,0,3.902-0.687,5.456-2.028l23.727-20.477l26.808,16.239 c3.091,1.873,7.04,1.543,9.779-0.82c2.738-2.363,3.642-6.22,2.242-9.553l-12.142-28.895l23.728-20.477 C204.903,291.152,205.806,287.295,204.405,283.961z M160.694,314.869l4.539,10.801c0,0-10.02-6.069-10.022-6.071 c-6.933-4.2-13.896,4.373-18.647,8.474l2.676-11.404c0.826-3.521-0.709-7.174-3.802-9.047l-10.02-6.071 c0,0,11.658-0.977,11.674-0.978c8.105-0.68,8.676-11.727,10.105-17.817c0,0,4.528,10.775,4.538,10.799 c3.159,7.516,13.816,4.63,20.068,4.106C166.988,301.819,157.455,307.161,160.694,314.869z"></path> </g> </g> <g> <g> <path d="M508.405,127.564c-7.115-4.925-9.523-14.443-5.604-22.14c1.896-3.722,0.747-8.273-2.687-10.651l-42.697-29.559 c-3.436-2.377-8.097-1.851-10.917,1.233c-3.271,3.583-7.951,5.638-12.836,5.638c-3.531,0-6.939-1.067-9.858-3.09 c-7.115-4.925-9.523-14.443-5.604-22.14c1.896-3.722,0.747-8.273-2.687-10.651L372.821,6.645 c-3.436-2.377-8.099-1.851-10.916,1.233c-3.272,3.583-7.953,5.636-12.837,5.638c-3.531,0-6.939-1.067-9.859-3.09 c-3.788-2.623-8.99-1.679-11.615,2.111l-48.265,69.714c-0.778-0.499-1.647-0.88-2.591-1.102l-50.557-11.862 c-4.066-0.955-8.213,1.245-9.705,5.147c-3.051,7.981-11.885,12.632-20.184,10.684c-8.422-1.977-14.151-9.95-13.324-18.548 c0.4-4.158-2.336-7.972-6.403-8.927l-50.556-11.863c-4.065-0.955-8.212,1.245-9.705,5.147 c-3.05,7.972-11.892,12.631-20.184,10.685c-4.483-1.053-8.979,1.732-10.034,6.22c0,0-12.5,53.268-12.511,53.316L0.223,433.747 c-1.053,4.489,1.732,8.981,6.22,10.034c8.424,1.977,14.152,9.951,13.325,18.548c-0.4,4.158,2.336,7.972,6.402,8.927l50.555,11.863 c4.074,0.955,8.213-1.244,9.705-5.146c7.029-18.385,35.387-11.661,33.51,7.863c-0.4,4.158,2.336,7.972,6.403,8.927l50.555,11.863 c4.017,0.939,8.23-1.289,9.705-5.148c2.549-6.668,9.057-11.149,16.195-11.149c3.682,0,6.845,1.572,10.295-0.565 c1.881-1.167,3.223-3.033,3.73-5.189l10.978-46.782c0,0,23.539,16.296,23.547,16.302c3.319,2.298,8.107,1.841,10.917-1.233 c3.271-3.583,7.951-5.636,12.835-5.636c3.531,0,6.94,1.069,9.86,3.09c3.791,2.623,8.99,1.677,11.615-2.113L510.515,139.18 C513.14,135.39,512.195,130.188,508.405,127.564z M226.923,368.429c0,0-12.581,53.615-12.582,53.619l-12.106,51.591 c-11.276,0.189-21.692,6.063-27.813,15.258l-37.974-8.911c-1.744-13.841-11.972-25.654-26.021-28.95 c-13.723-3.22-28.369,2.629-36.177,14.356l-37.973-8.911c-1.398-11.089-8.241-20.878-18.133-26.026l69.784-297.387l8.128,1.907 c0.641,0.15,1.281,0.223,1.914,0.223c3.787,0,7.216-2.597,8.12-6.443c1.053-4.489-1.732-8.981-6.221-10.034l-8.126-1.907 l8.927-38.043c11.276-0.189,21.691-6.063,27.813-15.258l37.974,8.911c1.744,13.841,11.971,25.654,26.02,28.952 c13.721,3.219,28.372-2.63,36.177-14.355l37.974,8.91c0.108,0.027,0.349,2.19,0.397,2.429c0.496,2.459,1.294,4.936,2.34,7.25 c3.139,6.953,8.614,12.832,15.395,16.351l-8.929,38.052l-8.128-1.907c-4.479-1.052-8.979,1.731-10.034,6.22 c-1.053,4.489,1.732,8.981,6.221,10.034l8.126,1.907l-13.276,56.578c0,0-1.45,6.18-1.451,6.184l-9.748,41.54 C247.54,280.566,226.923,368.426,226.923,368.429z M470.382,167.82l-6.864-4.753c-3.789-2.625-8.991-1.678-11.615,2.111 c-2.625,3.791-1.68,8.991,2.112,11.616l6.863,4.752L287.009,432.681c-3.784-1.42-7.806-2.154-11.913-2.154 c-7.047,0-13.882,2.192-19.562,6.16l-23.637-16.364l8.483-36.15l36.92,25.56c3.75,2.594,9.019,1.638,11.615-2.112l107.353-155.061 c2.625-3.791,1.68-8.991-2.11-11.616c-3.791-2.625-8.991-1.679-11.616,2.111L279.939,391.254l-35.461-24.551l16.277-69.365 l29.321-10.271l18.995,24.93c1.611,2.114,4.087,3.289,6.642,3.289c0.922,0,1.854-0.152,2.759-0.47 c3.413-1.195,5.669-4.452,5.586-8.069l-0.715-31.333l29.58-10.361c3.413-1.195,5.669-4.452,5.586-8.068 c-0.082-3.615-2.483-6.767-5.948-7.806l-30.021-9.005l-0.715-31.335c-0.082-3.616-2.483-6.767-5.948-7.806 c-3.465-1.039-7.203,0.272-9.262,3.245l-17.838,25.768l-11.43-3.428l21.491-91.583l10.519-15.194l107.133,74.171l-14.526,20.984 c-2.625,3.791-1.68,8.991,2.11,11.616c3.791,2.625,8.99,1.678,11.616-2.111c0,0,19.213-27.752,19.278-27.845 c2.582-3.73,1.603-9.044-2.11-11.616c0,0-120.644-83.524-120.861-83.675c-3.752-2.598-9.023-1.635-11.616,2.111l-3.623,5.234 c-0.1-0.027-0.196-0.062-0.297-0.086c-4.116-0.969-7.77-3.448-10.204-6.902c-0.018-0.025-0.033-0.052-0.051-0.078l19.201-27.734 c0,0,6.862,4.75,6.862,4.752c3.646,2.524,8.99,1.68,11.616-2.112c2.625-3.79,1.68-8.991-2.11-11.616l-6.863-4.752l22.241-32.125 c3.784,1.42,7.807,2.154,11.914,2.154c7.047,0,13.881-2.192,19.561-6.161l32.065,22.2c-3.446,13.518,1.741,28.256,13.607,36.471 c11.601,8.033,27.364,7.974,38.927-0.102l32.065,22.2c-2.76,10.83,0.021,22.446,7.338,30.86L470.382,167.82z M306.783,281.445 c0,0-7.098-9.314-7.101-9.32c-2.164-2.839-6.034-4-9.4-2.819l-11.056,3.872c3.639-5.257,11.529-12.768,6.445-19.443l-7.1-9.32 c0,0,11.211,3.363,11.223,3.366c7.824,2.346,12.336-7.687,15.929-12.876c0,0,0.267,11.701,0.267,11.712 c0.186,8.144,11.163,9.37,17.169,11.172l-11.057,3.872c-3.413,1.195-5.669,4.453-5.586,8.07L306.783,281.445z"></path> </g> </g> </g></svg>
                        <span :class="menuIconsOnly ? 'hidden' : ''">{{ $t('Tickets') }}</span>
                    </Link>
                </li>
                <li v-if="!isAgent" class="hover:bg-slate-50 hover:text-black rounded-[5px] px-2 truncate" :class="$page.url.startsWith('/campaign') ? 'bg-slate-50 text-black' : ''">
                    <Link rel="noopener noreferrer" href="/campaigns" class="flex items-center p-2 space-x-3 rounded-md">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 256 256"><path fill="currentColor" d="M216 80h-32V48a16 16 0 0 0-16-16H40a16 16 0 0 0-16 16v128a8 8 0 0 0 13 6.22L72 154v30a16 16 0 0 0 16 16h93.59L219 230.22a8 8 0 0 0 5 1.78a8 8 0 0 0 8-8V96a16 16 0 0 0-16-16M66.55 137.78L40 159.25V48h128v88H71.58a8 8 0 0 0-5.03 1.78M216 207.25l-26.55-21.47a8 8 0 0 0-5-1.78H88v-32h80a16 16 0 0 0 16-16V96h32Z"/></svg>
                        <span :class="menuIconsOnly ? 'hidden' : ''">{{ $t('Campaigns') }}</span>
                    </Link>
                </li>
                <li v-if="!isAgent" class="hover:bg-slate-50 hover:text-black rounded-[5px] px-2 truncate" :class="$page.url.startsWith('/template') ? 'bg-slate-50 text-black' : ''">
                    <Link rel="noopener noreferrer" href="/templates" class="flex items-center p-2 space-x-3 rounded-md">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 32 32"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M12 15h8m-8 4h8m8 5V11c0-1.105-.892-2-1.997-2H17c-2 0-2-3-5-3H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h20a2 2 0 0 0 2-2Z"/></svg>
                        <span :class="menuIconsOnly ? 'hidden' : ''">{{ $t('Message Templates') }}</span>
                    </Link>
                </li>
                <li v-if="!isAgent" class="hover:bg-slate-50 hover:text-black rounded-[5px] px-2 truncate" :class="$page.url.startsWith('/automation') ? 'bg-slate-50 text-black' : ''">
                    <Link rel="noopener noreferrer" href="/automation/basic" class="flex items-center p-2 space-x-3 rounded-md">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 32 32"><path fill="currentColor" d="M16 27c-3.6 0-7.1-1.8-9.2-5H12v-2H4v8h2v-3.7c2.5 3 6.1 4.7 10 4.7zm15-4v-2h-2.1c-.1-.6-.4-1.2-.7-1.8l1.5-1.5l-1.4-1.4l-1.5 1.5c-.5-.3-1.1-.6-1.8-.7V15h-2v2.1c-.6.1-1.2.4-1.8.7l-1.5-1.5l-1.4 1.4l1.5 1.5c-.3.5-.6 1.1-.7 1.8H17v2h2.1c.1.6.4 1.2.7 1.8l-1.5 1.5l1.4 1.4l1.5-1.5c.5.3 1.1.6 1.8.7V29h2v-2.1c.6-.1 1.2-.4 1.8-.7l1.5 1.5l1.4-1.4l-1.5-1.5c.3-.5.6-1.1.7-1.8zm-7 2c-1.7 0-3-1.3-3-3s1.3-3 3-3s3 1.3 3 3s-1.3 3-3 3m-4-15h5.2C21.9 4.9 15.1 3.5 10 6.8c-3.1 2-5 5.5-5 9.2H3C3 8.8 8.8 3 16 3c3.9 0 7.5 1.7 10 4.7V4h2v8h-8z"/></svg>
                        <span :class="menuIconsOnly ? 'hidden' : ''">{{ $t('Automation') }}</span>
                    </Link>
                </li>
                <li v-if="!isAgent" class="hover:bg-slate-50 hover:text-black rounded-[5px] px-2 truncate" :class="$page.url.startsWith('/saved-replies') ? 'bg-slate-50 text-black' : ''">
                    <Link rel="noopener noreferrer" href="/saved-replies" class="flex items-center p-2 space-x-3 rounded-md">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 14 14"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" d="m11 13.5l-4-4l-4 4v-12a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1Z"/></svg>
                        <span :class="menuIconsOnly ? 'hidden' : ''">{{ $t('Saved Replies') }}</span>
                    </Link>
                </li>
            </ul>

            <div class="px-4">
                <hr>
            </div>
            
            <ul class="pb-4 space-y-1 text-sm mt-2" v-if="!isAgent">
                <li class="hover:bg-slate-50 hover:text-black rounded-[5px] px-2 truncate" :class="$page.url.startsWith('/team') ? 'bg-slate-50 text-black' : ''">
                    <Link rel="noopener noreferrer" href="/team" class="flex items-center p-2 space-x-3 rounded-md">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20c0-1.657-2.239-3-5-3s-5 1.343-5 3m14-3c0-1.23-1.234-2.287-3-2.75M3 17c0-1.23 1.234-2.287 3-2.75m12-4.014a3 3 0 1 0-4-4.472m-8 4.472a3 3 0 0 1 4-4.472M12 14a3 3 0 1 1 0-6a3 3 0 0 1 0 6Z"/></svg>
                        <span :class="menuIconsOnly ? 'hidden' : ''">{{ $t('Team') }}</span>
                    </Link>
                </li>
                <li class="hover:bg-slate-50 hover:text-black rounded-[5px] px-2 truncate" :class="$page.url.startsWith('/integrations') ? 'bg-slate-50 text-black' : ''">
                    <Link rel="noopener noreferrer" href="/integrations" class="flex items-center p-2 space-x-3 rounded-md">
                       <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 32 32"><path fill="currentColor" d="M29 25h-2v-2h1v-4h-4v1h-2v-2a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1"/><path fill="currentColor" d="M24 30h-6a1 1 0 0 1-1-1v-6a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1m-5-2h4v-4h-4zm-4-8.142A3.993 3.993 0 1 1 20 16h2a6 6 0 1 0-7 5.91z"/><path fill="currentColor" d="m28.89 13.55l-2.31 2.03l-1.42-1.42l2.41-2.12l-2.36-4.08l-3.44 1.16a9.4 9.4 0 0 0-2.7-1.57L18.36 4h-4.72l-.71 3.55a8.9 8.9 0 0 0-2.71 1.57L6.79 7.96l-2.36 4.08l2.72 2.39a8.9 8.9 0 0 0 0 3.13l-2.72 2.4l2.36 4.08l3.44-1.16a9.4 9.4 0 0 0 2.7 1.57l.71 3.55H15v2h-1.36a2 2 0 0 1-1.96-1.61l-.51-2.52a11 11 0 0 1-1.31-.75l-2.43.82a2 2 0 0 1-.64.1a1.97 1.97 0 0 1-1.73-1L2.7 20.96a2 2 0 0 1 .41-2.51l1.92-1.68C5.01 16.51 5 16.26 5 16s.02-.51.04-.76l-1.93-1.69a2 2 0 0 1-.41-2.51l2.36-4.08a1.97 1.97 0 0 1 1.73-1a2 2 0 0 1 .64.1l2.42.82a12 12 0 0 1 1.32-.75l.51-2.52A2 2 0 0 1 13.64 2h4.72a2 2 0 0 1 1.96 1.61l.51 2.52a11 11 0 0 1 1.31.75l2.43-.82a2 2 0 0 1 .64-.1a1.97 1.97 0 0 1 1.73 1l2.36 4.08a2 2 0 0 1-.41 2.51"/></svg>
                        <span :class="menuIconsOnly ? 'hidden' : ''">{{ $t('Integrations') }}</span>
                    </Link>
                </li>
                <li class="hover:bg-slate-50 hover:text-black rounded-[5px] px-2 truncate" :class="$page.url.startsWith('/settings') ? 'bg-slate-50 text-black' : ''">
                    <Link rel="noopener noreferrer" href="/settings" class="md:flex items-center p-2 space-x-3 rounded-md hidden">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 16 16"><path fill="currentColor" fill-rule="evenodd" d="M3.5 2h-1v5h1zm6.1 5H6.4L6 6.45v-1L6.4 5h3.2l.4.5v1zm-5 3H1.4L1 9.5v-1l.4-.5h3.2l.4.5v1zm3.9-8h-1v2h1zm-1 6h1v6h-1zm-4 3h-1v3h1zm7.9 0h3.19l.4-.5v-.95l-.4-.5H11.4l-.4.5v.95zm2.1-9h-1v6h1zm-1 10h1v2h-1z" clip-rule="evenodd"/></svg>
                        <span :class="menuIconsOnly ? 'hidden' : ''">{{ $t('Settings') }}</span>
                    </Link>
                    <Link rel="noopener noreferrer" href="/settings/m" class="flex items-center p-2 space-x-3 rounded-md md:hidden">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 16 16"><path fill="currentColor" fill-rule="evenodd" d="M3.5 2h-1v5h1zm6.1 5H6.4L6 6.45v-1L6.4 5h3.2l.4.5v1zm-5 3H1.4L1 9.5v-1l.4-.5h3.2l.4.5v1zm3.9-8h-1v2h1zm-1 6h1v6h-1zm-4 3h-1v3h1zm7.9 0h3.19l.4-.5v-.95l-.4-.5H11.4l-.4.5v.95zm2.1-9h-1v6h1zm-1 10h1v2h-1z" clip-rule="evenodd"/></svg>
                        <span :class="menuIconsOnly ? 'hidden' : ''">{{ $t('Settings') }}</span>
                    </Link>
                </li>
                <li class="hover:bg-slate-50 hover:text-black rounded-[5px] px-2 truncate" :class="$page.url.startsWith('/billing') || $page.url.startsWith('/subscription') ? 'bg-slate-50 text-black' : ''">
                    <Link rel="noopener noreferrer" href="/billing" class="flex items-center p-2 space-x-3 rounded-md">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 11v4.8c0 1.12 0 1.68.218 2.108a2 2 0 0 0 .874.874c.427.218.987.218 2.105.218h11.606c1.118 0 1.677 0 2.104-.218c.377-.192.683-.498.875-.874c.218-.428.218-.986.218-2.104V11M3 11V9m0 2h18M3 9v-.8c0-1.12 0-1.68.218-2.108c.192-.377.497-.682.874-.874C4.52 5 5.08 5 6.2 5h11.6c1.12 0 1.68 0 2.107.218c.377.192.683.497.875.874c.218.427.218.987.218 2.105V9M3 9h18M7 15h4m10-4V9"/></svg>
                        <span :class="menuIconsOnly ? 'hidden' : ''">{{ $t('Billing and subscription') }}</span>
                    </Link>
                </li>
                <li class="hover:bg-slate-50 hover:text-black rounded-[5px] px-2 truncate" :class="$page.url.startsWith('/support') ? 'bg-slate-50 text-black' : ''">
                    <Link rel="noopener noreferrer" href="/support" class="flex items-center p-2 space-x-3 rounded-md">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" fill-rule="evenodd" d="M12.26 21.997a10.276 10.276 0 0 1-.52 0a10.004 10.004 0 0 1-8.983-6.173a10.034 10.034 0 0 1 .017-7.69A10.015 10.015 0 0 1 4.908 4.95l.042-.042a10.015 10.015 0 0 1 3.167-2.126a10.034 10.034 0 0 1 7.753-.006a10.015 10.015 0 0 1 3.186 2.138l.03.03c.913.917 1.65 2.01 2.153 3.223a10.012 10.012 0 0 1 .76 3.985a10.004 10.004 0 0 1-6.226 9.112a10.013 10.013 0 0 1-3.512.733Zm1.772-6.55l2.874 2.873a8.004 8.004 0 0 1-9.812 0l2.874-2.874a4.007 4.007 0 0 0 4.064 0Zm-5.478-1.415L5.68 16.906a8.004 8.004 0 0 1 0-9.812l2.874 2.874a4.007 4.007 0 0 0 0 4.064Zm1.528-1.463a2.01 2.01 0 0 1-.014-1.087a1.99 1.99 0 0 1 .518-.896a1.99 1.99 0 0 1 1.932-.518c.328.088.639.26.896.518a1.99 1.99 0 0 1 .518 1.932c-.088.328-.26.639-.518.896a1.99 1.99 0 0 1-1.932.518a1.991 1.991 0 0 1-.896-.518a1.99 1.99 0 0 1-.504-.845Zm3.95-4.015a4.007 4.007 0 0 0-4.064 0L7.094 5.68a8.004 8.004 0 0 1 9.812 0l-2.874 2.874Zm4.288 8.352a8.004 8.004 0 0 0 0-9.812l-2.874 2.874a4.007 4.007 0 0 1 0 4.064l2.874 2.874Z" clip-rule="evenodd"/></svg>
                        <span :class="menuIconsOnly ? 'hidden' : ''">{{ $t('Support') }}</span>
                    </Link>
                </li>
                <li v-if="user.teams[0]['role'] === 'owner'" class="hover:bg-slate-50 hover:text-black rounded-[5px] px-2 truncate">
                    <Link rel="noopener noreferrer" href="/developer" class="flex items-center p-2 space-x-3 rounded-md">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M7.312 9H5.688L3.5 15h1.607l.446-1.226h1.894L7.893 15H9.5Zm-1.394 3.774L6.5 11.18l.582 1.595ZM14.744 9h-3.5v6h1.5v-2h2a1.473 1.473 0 0 0 1.5-1.5v-1a1.473 1.473 0 0 0-1.5-1.5m0 2.5h-2v-1h2ZM18 9h1.5v6H18z"/><path fill="currentColor" d="M22 6v12H2V6zm0-2H2a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h20a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2"/></svg>
                        <span :class="menuIconsOnly ? 'hidden' : ''">{{ $t('Developer') }}</span>
                    </Link>
                </li>
            </ul>
        </div>
    </div>

    <div v-if="menuIconsOnly === false" @click="switchTeams()" class="border-2 border-primary text-sm rounded-[5px] mb-1 m-3 py-2 px-4 flex items-center justify-between cursor-pointer">
        <div class="flex space-x-1">
            <span>{{ $t('Team') }}:</span>
            <span class="text-ellipsis w-[120px] truncate">{{ props.organization.name }}</span>
        </div>
        <span>
            <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"><path fill="currentColor" d="M19.696 8.72a1.22 1.22 0 0 1-.3.64l-6.09 6.76a1.85 1.85 0 0 1-.58.46a1.7 1.7 0 0 1-1.42.03a1.75 1.75 0 0 1-.62-.42l-6.1-6.83a1.28 1.28 0 0 1-.31-.64a1.31 1.31 0 0 1 .56-1.26a1.36 1.36 0 0 1 .68-.21h13a1.293 1.293 0 0 1 1.15.76c.081.228.092.476.03.71"></path></svg>
        </span>
    </div>
    
    <div class="flex items-center m-3 p-2 rounded-xl h-14 py-1 md:py-1 mt-2 gap-x-4 bg-slate-50" :class="!menuIconsOnly ? 'justify-between' : 'justify-center'">
        <div v-if="!menuIconsOnly" class="flex space-x-2">
            <div class="rounded-xl p-1 bg-slate-200">
                <img v-if="user.avatar" class="rounded-full w-9 h-9" :src="'/media/' + user.avatar">
                <div v-else class="rounded-full w-9 h-9 flex justify-center items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="6" r="4"/><path stroke-linecap="round" d="M19.998 18c.002-.164.002-.331.002-.5c0-2.485-3.582-4.5-8-4.5s-8 2.015-8 4.5S4 22 12 22c2.231 0 3.84-.157 5-.437"/></g></svg>
                </div>
            </div>
            <div>
                <h2 class="text-[15px] capitalize truncate w-[6em]"  :class="menuIconsOnly ? 'hidden' : ''">{{ user.first_name + ' ' + user.last_name }}</h2>
                <span class="flex items-center space-x-1" :class="menuIconsOnly ? 'hidden' : ''">
                    <span @click="openModal" class="text-sm hover:underline dark:text-gray-400 cursor-pointer">{{ $t('View profile') }}</span>
                </span>
            </div>
        </div>
        <Link href="/logout" class="hover:bg-[#F6F7F9] hover:rounded-full w-[fit-content] p-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m12 15l3-3m0 0l-3-3m3 3H4m5-4.751V7.2c0-1.12 0-1.68.218-2.108c.192-.377.497-.682.874-.874C10.52 4 11.08 4 12.2 4h4.6c1.12 0 1.68 0 2.107.218c.377.192.683.497.875.874c.218.427.218.987.218 2.105v9.607c0 1.118 0 1.677-.218 2.104a2.002 2.002 0 0 1-.875.874c-.427.218-.986.218-2.104.218h-4.606c-1.118 0-1.678 0-2.105-.218a2 2 0 0 1-.874-.874C9 18.48 9 17.92 9 16.8v-.05"/></svg>
        </Link>
    </div>

    <Modal :label="$t('Switch teams')" :isOpen="isLocationSwitchModalOpen">
        <div class="mt-2 grid grid-cols-1 gap-x-6">
            <div class="pt-3 space-y-2 text-sm">
                <div v-for="(item, index) in props.organizations" :key="index" @click="selectOrganization(item.organization.uuid)" class="flex gap-x-8 hover:bg-slate-200 rounded-lg py-1 justify-between items-center w-full cursor-pointer border border-slate-100 pl-1 pr-2">
                    <div class="flex items-center gap-x-2">
                        <span class="bg-slate-200 w-10 h-10 rounded-lg flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><g fill="none" fill-rule="evenodd"><path d="M24 0v24H0V0h24ZM12.593 23.258l-.011.002l-.071.035l-.02.004l-.014-.004l-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113l-.013.002l-.185.093l-.01.01l-.003.011l.018.43l.005.012l.008.007l.201.093c.012.004.023 0 .029-.008l.004-.014l-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014l-.034.614c0 .012.007.02.017.024l.015-.002l.201-.093l.01-.008l.004-.011l.017-.43l-.003-.012l-.01-.01l-.184-.092Z"/><path fill="currentColor" d="M17 3.722v5.497l2.864.716A1.5 1.5 0 0 1 21 11.39V19a1 1 0 1 1 0 2H3a1 1 0 1 1 0-2v-7.69a1.5 1.5 0 0 1 .83-1.343L7 8.382V6.347a1.5 1.5 0 0 1 .973-1.405l7-2.625A1.5 1.5 0 0 1 17 3.722Zm-2 .721l-6 2.25V19h6V4.443Zm2 6.838V19h2v-7.22l-2-.5Zm-10-.663l-2 1V19h2v-8.382Z"/></g></svg>
                        </span>
                        <div>
                            <h3>{{ item.organization.name }}</h3>
                        </div>
                    </div>
                    <span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 20 20"><path fill="currentColor" fill-rule="evenodd" d="M5 2.643v14.765c.092.32.299.511.619.572c.32.061.633-.024.94-.255l8.107-6.993A.944.944 0 0 0 15 10a.94.94 0 0 0-.334-.73L6.58 2.295c-.232-.197-.639-.383-1.061-.253c-.282.087-.455.287-.519.6"/></svg>
                    </span>
                </div>
            </div>
            <div class="mt-4 border-t pt-4">
                <button type="button" @click.self="isLocationSwitchModalOpen = false" class="inline-flex justify-center rounded-md border border-transparent bg-slate-50 px-4 py-2 text-sm text-slate-500 hover:bg-slate-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 mr-4">{{ $t('Cancel') }}</button>
            </div>
        </div>
    </Modal>
    <ProfileModal :user="props.user" :organization="props.organization" :isOpen="isOpen" role="user" @close="closeModal()"/>
</template>
<script setup>
    import axios from "axios"; 
    import { Link, useForm, usePage } from "@inertiajs/vue3";
    import { defineProps, ref, computed } from "vue";
    import FormInput from '@/Components/FormInput.vue';
    import Modal from '@/Components/Modal.vue';
    import ProfileModal from '@/Components/ProfileModal.vue';
    import LangToggle from '@/Components/LangToggle.vue';

    const props = defineProps(['config', 'user', 'organization', 'organizations', 'isSidebarOpen', 'menuIconsOnly', 'unreadMessages']);

    const languages = computed(() => usePage().props.languages);
    const currentLanguage = computed(() => usePage().props.currentLanguage);
    const isOpen = ref(false);
    const isLocationSwitchModalOpen = ref(false);
    const showDropdown1 = ref(false);

    const emit = defineEmits(['closeSidebar']);
    const role = computed(() => usePage().props.auth.user.teams[0].role);
    const isAgent = computed(() => role.value === 'agent');
    const form = useForm({
        uuid: null,
    })

    const getValueByKey = (key) => {
        const found = props.config.find(item => item.key === key);
        return found ? found.value : '';
    };

    const closeSidebar = () => {
        emit('closeSidebar', true);
    }

    const toggleDropdown = (type) => {
        if(type === 'dropdown1'){
            showDropdown1.value = !showDropdown1.value;
        }
    }

    const closeModal = () => {
        isOpen.value = false
    }

    const openModal = () => {
        isOpen.value = true;
        emit('closeSidebar', true);
    }

    const switchTeams = () => {
        isLocationSwitchModalOpen.value = true; 
        emit('closeSidebar', true);
    }

    const selectOrganization = (uuid) => {
        form.uuid = uuid;
        submitForm();
    }

    const submitForm = async () => {
        form.post('/organization', {
            preserveScroll: true,
            onFinish: isLocationSwitchModalOpen.value = false,
        })
    };
</script>