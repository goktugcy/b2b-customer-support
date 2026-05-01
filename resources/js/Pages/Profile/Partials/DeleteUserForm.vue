<script setup lang="ts">
import { nextTick, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Button from '@/Components/ui/button/Button.vue'
import Input from '@/Components/ui/input/Input.vue'
import Label from '@/Components/ui/label/Label.vue'
import Modal from '@/Components/Modal.vue'
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

    <Modal :show="confirmingUserDeletion" max-width="lg" @close="closeModal">
      <div class="p-6">
        <h2 class="text-lg font-semibold">Delete account</h2>

        <p class="mt-2 text-sm leading-6 text-muted-foreground">
          Enter your password to confirm that you want to permanently delete your account.
        </p>

        <div class="mt-6">
          <Label for="password" class="sr-only">Password</Label>
          <Input id="password" ref="passwordInput" v-model="form.password" class="mt-1" type="password" placeholder="Password" @keyup.enter="deleteUser" />
          <FieldError :message="form.errors.password" />
        </div>

        <div class="mt-6 flex justify-end gap-3">
          <Button type="button" variant="secondary" @click="closeModal">Cancel</Button>
          <Button type="button" variant="danger" :disabled="form.processing" @click="deleteUser">Delete account</Button>
        </div>
      </div>
    </Modal>
  </section>
</template>
