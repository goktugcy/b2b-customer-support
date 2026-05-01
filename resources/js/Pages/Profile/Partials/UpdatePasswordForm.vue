<script setup lang="ts">
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Button from '@/Components/ui/button/Button.vue'
import Input from '@/Components/ui/input/Input.vue'
import Label from '@/Components/ui/label/Label.vue'
import FieldError from '@/Components/shared/FieldError.vue'

type InputExpose = {
  focus: () => void
}

const passwordInput = ref<InputExpose | null>(null)
const currentPasswordInput = ref<InputExpose | null>(null)

const form = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
})

const updatePassword = () => {
  form.put(route('password.update'), {
    preserveScroll: true,
    onSuccess: () => form.reset(),
    onError: () => {
      if (form.errors.password) {
        form.reset('password', 'password_confirmation')
        passwordInput.value?.focus()
      }

      if (form.errors.current_password) {
        form.reset('current_password')
        currentPasswordInput.value?.focus()
      }
    },
  })
}
</script>

<template>
  <form class="space-y-5" @submit.prevent="updatePassword">
    <div>
      <Label for="current_password">Current password</Label>
      <Input id="current_password" ref="currentPasswordInput" v-model="form.current_password" class="mt-1" type="password" autocomplete="current-password" />
      <FieldError :message="form.errors.current_password" />
    </div>

    <div>
      <Label for="password">New password</Label>
      <Input id="password" ref="passwordInput" v-model="form.password" class="mt-1" type="password" autocomplete="new-password" />
      <FieldError :message="form.errors.password" />
    </div>

    <div>
      <Label for="password_confirmation">Confirm password</Label>
      <Input id="password_confirmation" v-model="form.password_confirmation" class="mt-1" type="password" autocomplete="new-password" />
      <FieldError :message="form.errors.password_confirmation" />
    </div>

    <div class="flex items-center gap-4">
      <Button type="submit" :disabled="form.processing">Save</Button>
      <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0" leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
        <p v-if="form.recentlySuccessful" class="text-sm text-muted-foreground">Saved.</p>
      </Transition>
    </div>
  </form>
</template>
