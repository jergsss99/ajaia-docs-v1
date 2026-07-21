<template>
    <div class="app-shell">
        <header>
            <button class="brand" @click="current = null">
                <span>Aj</span>Ajaia Docs
            </button>
            <div class="header-actions">
                <label class="user-picker"
                    >Viewing as
                    <select v-model="currentUserId" @change="switchUser">
                        <option
                            v-for="user in users"
                            :key="user.id"
                            :value="user.id"
                        >
                            {{ user.name }}
                        </option>
                    </select></label
                >
                <button
                    v-if="current && isOwner"
                    class="share-button"
                    @click="showShare = true"
                >
                    Share
                </button>
            </div>
        </header>

        <main v-if="!current" class="dashboard">
            <section class="welcome-row">
                <div>
                    <p class="eyebrow">YOUR WORKSPACE</p>
                    <h1>Good work starts with a clear page.</h1>
                    <p>
                        Create, import, and share documents without the clutter.
                    </p>
                </div>
                <button class="primary" @click="createDocument">
                    ＋ New document
                </button>
            </section>
            <section class="document-panel">
                <div class="panel-header">
                    <div>
                        <h2>Documents</h2>
                        <p>{{ documents.length }} available to you</p>
                    </div>
                    <div class="tools">
                        <input
                            v-model="query"
                            placeholder="Search documents…"
                        /><label class="import-button"
                            >↑ Import .txt or .md<input
                                type="file"
                                accept=".txt,.md,text/plain,text/markdown"
                                @change="importFile"
                        /></label>
                    </div>
                </div>
                <div v-if="visibleDocuments.length" class="document-grid">
                    <button
                        v-for="doc in visibleDocuments"
                        :key="doc.id"
                        class="document-card"
                        @click="openDocument(doc.id)"
                    >
                        <div class="paper-preview">
                            <div></div>
                            <div></div>
                            <div></div>
                            <div class="short"></div>
                        </div>
                        <div class="card-meta">
                            <strong>{{ doc.title }}</strong
                            ><span
                                ><em :class="doc.access_type">{{
                                    doc.access_type
                                }}</em>
                                {{
                                    doc.access_type === "shared"
                                        ? `by ${doc.owner.name}`
                                        : "by you"
                                }}</span
                            ><small
                                >Edited
                                {{
                                    new Date(
                                        doc.updated_at,
                                    ).toLocaleDateString()
                                }}</small
                            >
                        </div>
                    </button>
                </div>
                <div v-else class="empty">
                    <span>✦</span>
                    <h3>No documents here yet</h3>
                    <p>
                        Create a blank document or import a .txt or .md file
                        (max 2 MB).
                    </p>
                </div>
            </section>
        </main>

        <main v-else class="editor-screen">
            <div class="document-topbar">
                <button class="back" @click="current = null">←</button>
                <div>
                    <input
                        v-model="current.title"
                        maxlength="150"
                        @blur="save"
                    />
                    <p>
                        <span :class="current.access_type">{{
                            current.access_type
                        }}</span>
                        · Owner: {{ current.owner.name }} · {{ status }}
                    </p>
                </div>
                <button class="save" @click="save">Save changes</button>
            </div>
            <div class="toolbar">
                <button @click="format('bold')"><b>B</b></button
                ><button @click="format('italic')"><i>I</i></button
                ><button @click="format('underline')"><u>U</u></button
                ><span></span
                ><button @click="format('formatBlock', 'h1')">H1</button
                ><button @click="format('formatBlock', 'h2')">H2</button
                ><button @click="format('formatBlock', 'p')">Text</button
                ><span></span
                ><button @click="format('insertUnorderedList')">• List</button
                ><button @click="format('insertOrderedList')">1. List</button>
            </div>
            <div class="page-wrap">
                <article
                    ref="editor"
                    class="editor"
                    contenteditable="true"
                    spellcheck="true"
                    @input="status = 'Unsaved changes'"
                    @keydown.ctrl.s.prevent="save"
                ></article>
            </div>
        </main>

        <div v-if="error" class="toast" @click="error = ''">
            {{ error }} <b>×</b>
        </div>
        <div
            v-if="showShare"
            class="modal-backdrop"
            @click.self="showShare = false"
        >
            <form class="modal" @submit.prevent="share">
                <button
                    type="button"
                    class="modal-close"
                    @click="showShare = false"
                >
                    ×
                </button>
                <p class="eyebrow">COLLABORATE</p>
                <h2>Share “{{ current.title }}”</h2>
                <p>
                    Choose one of the seeded users by email. Collaborators can
                    edit this document.
                </p>
                <input
                    v-model="shareEmail"
                    type="email"
                    required
                    placeholder="noah@example.com"
                />
                <div v-if="current.collaborators?.length" class="people">
                    <small>Already shared with</small
                    ><span
                        v-for="person in current.collaborators"
                        :key="person.id"
                        >{{ person.name }} · {{ person.email }}</span
                    >
                </div>
                <button class="primary" type="submit">Grant access</button>
            </form>
        </div>
    </div>
</template>
<script setup>
import { computed, nextTick, onMounted, ref } from "vue";

const users = ref([]),
    documents = ref([]),
    currentUserId = ref(null),
    current = ref(null);
const editor = ref(null),
    query = ref(""),
    status = ref("Ready"),
    error = ref(""),
    showShare = ref(false),
    shareEmail = ref("");
const csrf = document.querySelector('meta[name="csrf-token"]').content;
const visibleDocuments = computed(() =>
    documents.value.filter((d) =>
        d.title.toLowerCase().includes(query.value.toLowerCase()),
    ),
);
const isOwner = computed(() => current.value?.owner_id === currentUserId.value);

async function api(path, options = {}) {
    error.value = "";
    const separator = path.includes("?") ? "&" : "?";
    const response = await fetch(
        `${path}${separator}user_id=${currentUserId.value}`,
        {
            ...options,
            headers: {
                Accept: "application/json",
                "X-CSRF-TOKEN": csrf,
                ...(options.headers || {}),
            },
        },
    );
    const body = await response.json().catch(() => ({}));
    if (!response.ok)
        throw new Error(
            body.message ||
                Object.values(body.errors || {})[0]?.[0] ||
                "Something went wrong.",
        );
    return body;
}
async function loadDocuments() {
    try {
        documents.value = await api("/api/documents");
    } catch (e) {
        error.value = e.message;
    }
}
async function switchUser() {
    current.value = null;
    localStorage.setItem("ajaia-user", currentUserId.value);
    await loadDocuments();
}
async function openDocument(id) {
    try {
        current.value = await api(`/api/documents/${id}`);
        await nextTick();
        editor.value.innerHTML = current.value.content || "<p><br></p>";
    } catch (e) {
        error.value = e.message;
    }
}
async function createDocument() {
    try {
        const doc = await api("/api/documents", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                title: "Untitled document",
                content: "<h1>Start writing</h1><p></p>",
            }),
        });
        await loadDocuments();
        await openDocument(doc.id);
    } catch (e) {
        error.value = e.message;
    }
}
async function save() {
    if (!current.value) return;
    status.value = "Saving…";
    try {
        current.value = await api(`/api/documents/${current.value.id}`, {
            method: "PUT",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                title: current.value.title,
                content: editor.value.innerHTML,
            }),
        });
        status.value = "Saved";
        await loadDocuments();
        setTimeout(() => (status.value = "Ready"), 1500);
    } catch (e) {
        error.value = e.message;
        status.value = "Not saved";
    }
}
function format(command, value = null) {
    document.execCommand(command, false, value);
    editor.value.focus();
}
async function importFile(event) {
    const file = event.target.files[0];
    if (!file) return;
    const data = new FormData();
    data.append("file", file);
    try {
        const doc = await api("/api/documents/import", {
            method: "POST",
            body: data,
        });
        await loadDocuments();
        await openDocument(doc.id);
    } catch (e) {
        error.value = e.message;
    }
    event.target.value = "";
}
async function share() {
    try {
        current.value = await api(`/api/documents/${current.value.id}/share`, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ email: shareEmail.value }),
        });
        showShare.value = false;
        shareEmail.value = "";
        status.value = "Shared";
    } catch (e) {
        error.value = e.message;
    }
}
onMounted(async () => {
    users.value = await fetch("/api/users").then((r) => r.json());
    currentUserId.value =
        Number(localStorage.getItem("ajaia-user")) || users.value[0]?.id;
    await loadDocuments();
});
</script>