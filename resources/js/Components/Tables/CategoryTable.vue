<script setup>
    import Table from '@/Components/Table.vue';
    import TableHeader from '@/Components/TableHeader.vue';
    import TableHeaderRow from '@/Components/TableHeaderRow.vue';
    import TableHeaderRowItem from '@/Components/TableHeaderRowItem.vue';
    import TableBody from '@/Components/TableBody.vue';
    import TableBodyRow from '@/Components/TableBodyRow.vue';
    import TableBodyRowItem from '@/Components/TableBodyRowItem.vue';
    import Dropdown from '@/Components/Dropdown.vue';
    import DropdownItemGroup from '@/Components/DropdownItemGroup.vue';
    import DropdownItem from '@/Components/DropdownItem.vue';



</script>

<template>
    <div class="bg-slate-100 md:bg-slate-50 rounded-[0.5rem]">
        <table class="w-full">
            <TableHeader>
                <TableHeaderRow>
                    <TableHeaderRowItem :position="'first'" class="pl-2 py-2 pb-2">Category Name</TableHeaderRowItem>
                    <TableHeaderRowItem class="pl-2 py-2 pb-2">Description</TableHeaderRowItem>
                    <TableHeaderRowItem class="pl-2 py-2 pb-2">Actions</TableHeaderRowItem>
                    <TableHeaderRowItem :position="'last'" class="pl-2 py-2 pb-2"></TableHeaderRowItem>
                </TableHeaderRow>
            </TableHeader>
            <TableBody>
                <TableBodyRow  >
                    <TableBodyRowItem class="pl-4 py-4">Name</TableBodyRowItem>
                    <TableBodyRowItem class="pl-2 py-2 pb-2">Description</TableBodyRowItem>
                    <TableBodyRowItem class="pl-2 py-2 pb-2"></TableBodyRowItem>
                    <TableBodyRowItem class="pr-4 float-right">
                        <Dropdown :align="'right'" class="mt-2">
                            <button class="inline-flex w-full justify-center rounded-md text-sm font-medium text-black hover:bg-opacity-30 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-opacity-75">
                                <span class="hover:bg-[#F6F7F9] hover:rounded-full w-[fit-content] p-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
                                        <path fill="currentColor" d="M12 16a2 2 0 0 1 2 2a2 2 0 0 1-2 2a2 2 0 0 1-2-2a2 2 0 0 1 2-2m0-6a2 2 0 0 1 2 2a2 2 0 0 1-2 2a2 2 0 0 1-2-2a2 2 0 0 1 2-2m0-6a2 2 0 0 1 2 2a2 2 0 0 1-2 2a2 2 0 0 1-2-2a2 2 0 0 1 2-2Z"/>
                                    </svg>
                                </span>
                            </button>
                            <template #items>
                                <DropdownItemGroup>
                                    <DropdownItem as="button">Edit</DropdownItem>
                                    <DropdownItem as="button">Delete</DropdownItem>
                                </DropdownItemGroup>
                            </template>
                        </Dropdown>
                    </TableBodyRowItem>            
                </TableBodyRow>
            </TableBody>
        </table>
    </div>

</template>
