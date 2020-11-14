<template>
  <footer class="footer">
    <button v-if="isLogin" class="button button--link" @click="logout">
      Logout
    </button>
    <RouterLink v-else class="button button--link" to="/login">
      Login / Register
    </RouterLink>
  </footer>
</template>

<script>
export default {
  methods: {
    async logout() {
      await this.$store.dispatch("auth/logout");

      if (this.apiStatus) {
        this.$router.push("/login");
      }
    },
  },
  computed: {
    apiStatus() {
      return this.$store.state.auth.apiStatus;
    },
    isLogin() {
      return this.$store.getters["auth/check"];
    },
  },
};
</script>
