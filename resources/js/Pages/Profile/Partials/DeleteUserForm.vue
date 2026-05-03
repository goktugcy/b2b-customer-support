<script setup lang="ts">
import { nextTick, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Button from '@/Components/ui/button/Button.vue'
import Input from '@/Components/ui/input/Input.vue'
import Label from '@/Components/ui/label/Label.vue'
import Dialog from '@/Components/ui/dialog/Dialog.vue'
import DialogContent from '@/Components/ui/dialog/DialogContent.vue'
import DialogDescription from '@/Components/ui/dialog/DialogDescription.vue'
import DialogFooter from '@/Components/ui/dialog/DialogFooter.vue'
import DialogHeader from '@/Components/ui/dialog/DialogHeader.vue'
import DialogTitle from '@/Components/ui/dialog/DialogTitle.vue'
import FieldError from '@/Components/shared/FieldError.vue'

type InputExpose = {
  focus: () => void
}

const confirmingUserDeletion = ref(false)
const passwordInput = ref<InputExpose | null>(null)

const form = useForm({
  password: '',
})

const confirmUserDeletion = () => {
  confirmingUserDeletion.value = true

  nextTick(() => passwordInput.value?.focus())
}

const closeModal = () => {
  confirmingUserDeletion.value = false

  form.clearErrors()
  form.reset()
}

const deleteUser = () => {
  form.delete(route('profile.destroy'), {
    preserveScroll: true,
    onSuccess: () => closeModal(),
    onError: () => passwordInput.value?.focus(),
    onFinish: () => form.reset(),
  })
}
</script>

<template>
  <section class="space-y-4">
    <p class="text-sm leading-6 text-muted-foreground">
      Once your account is deleted, all of its resources and data will be permanently deleted.
    </p>

    <Button type="button" variant="danger" @click="confirmUserDeletion">Delete account</Button>

    <Dialog v-model:open="confirmingUserDeletion">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Delete account</DialogTitle>
          <DialogDescription>Enter your password to confirm that you want to permanently delete your account.</DialogDescription>
        </DialogHeader>

        <div>
          <Label for="password" class="sr-only">Password</Label>
          <Input id="password" ref="passwordInput" v-model="form.password" class="mt-1" type="password" placeholder="Password" @keyup.enter="deleteUser" />
          <FieldError :message="form.errors.password" />
        </div>

        <DialogFooter>
          <Button type="button" variant="secondary" @click="closeModal">Cancel</Button>
          <Button type="button" variant="danger" :disabled="form.processing" @click="deleteUser">Delete account</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </section>
</template>
