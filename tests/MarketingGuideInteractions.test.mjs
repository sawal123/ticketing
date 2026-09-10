import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import { runInNewContext } from 'node:vm';

const source = readFileSync(new URL('../resources/views/marketing-guide/dynamic.blade.php', import.meta.url), 'utf8');
const script = source.match(/<script>([\s\S]*?)<\/script>/)[1];

function element(attributes = {}) {
    const classes = new Set();
    return {
        style: {}, listeners: {},
        classList: {
            add: value => classes.add(value), remove: value => classes.delete(value),
            contains: value => classes.has(value),
            toggle: value => classes.has(value) ? classes.delete(value) : classes.add(value),
        },
        getAttribute: name => attributes[name] ?? null,
        addEventListener(name, callback) { (this.listeners[name] ??= []).push(callback); },
        click() { this.listeners.click?.forEach(callback => callback.call(this, { preventDefault() {} })); },
        scrollIntoView() { this.scrolled = true; },
    };
}

function guide(href = '#faq', height = 1600) {
    const elements = Object.fromEntries(['menuToggle', 'drawer', 'drawerOverlay', 'progressBar', 'faq'].map(id => [id, element()]));
    const cta = element({ href });
    const nav = element({ href: '#faq', 'data-id': 'faq' });
    const question = element();
    question.nextElementSibling = element();
    const section = { id: 'faq', offsetTop: 0, clientHeight: 500 };
    const document = {
        documentElement: { scrollHeight: height },
        getElementById: id => elements[id] ?? null,
        querySelector(selector) {
            // Only this fixture's valid CSS selector resolves a target.
            // Malformed selectors throw, as the DOM selector API requires.
            if (selector === '#faq') return elements.faq;
            throw new SyntaxError(`Invalid selector: ${selector}`);
        },
        querySelectorAll(selector) {
            return ({
                'section': [section], '.nav-link': [nav], '[data-id="faq"]': [nav],
                '.drawer-content .nav-link': [nav], '.faq-question': [question],
                '.faq-question.active': question.classList.contains('active') ? [question] : [],
                '.nav-link, a[href^="#"]': [nav, cta],
            })[selector] ?? [];
        },
    };
    runInNewContext(script, { document, window: { scrollY: 0, innerHeight: 800, addEventListener() {} } });
    return { ...elements, nav, cta, question };
}

test('drawer, active navigation, FAQ, and CTA interactions', () => {
    const page = guide();
    assert.equal(page.nav.classList.contains('active'), true);
    page.menuToggle.click();
    assert.equal(page.drawer.classList.contains('active'), true);
    page.drawerOverlay.click();
    assert.equal(page.drawer.classList.contains('active'), false);
    page.menuToggle.click();
    page.nav.click();
    assert.equal(page.drawer.classList.contains('active'), false);
    page.question.click();
    assert.equal(page.question.nextElementSibling.classList.contains('active'), true);
    page.question.click();
    assert.equal(page.question.nextElementSibling.classList.contains('active'), false);
    page.cta.click();
    assert.equal(page.faq.scrolled, true);
});

for (const href of ['#missing]', '#123', '#', '#faq']) {
    test(`CTA anchor ${href} does not throw a selector error`, () => {
        const page = guide(href);
        assert.doesNotThrow(() => page.cta.click());
    });
}

test('a short guide has finite progress', () => {
    const page = guide('#faq', 800);
    assert.equal(page.progressBar.style.width, '0%');
});

const editorSource = readFileSync(new URL('../resources/views/livewire/admin/marketing-guide-content-editor.blade.php', import.meta.url), 'utf8');
const editorContext = {};
runInNewContext(editorSource.match(/<script>([\s\S]*?)<\/script>/)[1], editorContext);

test('editor modal shell opens before the isolated Livewire request resolves', async () => {
    const events = [];
    const calls = [];
    let resolveRequest;
    editorContext.CustomEvent = class {
        constructor(type, options) {
            this.type = type;
            this.detail = options.detail;
        }
    };
    editorContext.document = {
        querySelector: () => ({ getAttribute: name => name === 'wire:id' ? 'modal-123' : null }),
    };
    editorContext.window = {
        dispatchEvent: event => events.push(event),
        Livewire: {
            find: id => ({
                call(action, resourceId) {
                    calls.push({ id, action, resourceId });
                    return new Promise(resolve => { resolveRequest = resolve; });
                },
            }),
        },
    };

    const request = editorContext.mgeOpenEditorModal('mge-block-modal', 'Edit Block', 'openBlockEditor', 42);

    assert.equal(events[0].detail.name, 'mge-block-modal');
    assert.equal(events[0].detail.title, 'Edit Block');
    assert.equal(events[0].detail.loading, true);
    assert.deepEqual(calls, [{ id: 'modal-123', action: 'openBlockEditor', resourceId: 42 }]);

    resolveRequest();
    assert.equal(await request, true);
});

test('editor modal request failure replaces loading with a safe error', async () => {
    const events = [];
    editorContext.document = {
        querySelector: () => ({ getAttribute: () => 'modal-123' }),
    };
    editorContext.window = {
        dispatchEvent: event => events.push(event),
        Livewire: {
            find: () => ({ call: () => Promise.reject(new Error('sensitive server detail')) }),
        },
    };

    const result = await editorContext.mgeOpenEditorModal('mge-section-modal', 'Edit Section', 'openSectionEditor', 7);
    const error = events.find(event => event.type === 'mge-modal-error');

    assert.equal(result, false);
    assert.equal(error.detail.name, 'mge-section-modal');
    assert.equal(error.detail.message.includes('sensitive server detail'), false);
});

for (const [type, key, field] of [
    ['workflow', 'steps', 'title'], ['flow', 'boxes', 'label'], ['cards', 'cards', 'title'],
    ['tickets', 'tickets', 'type'], ['stats', 'stats', 'label'], ['faq', 'items', 'question'],
]) {
    test(`${type} nested reorder preserves the edited content on save`, () => {
        const editor = editorContext.mgeBlockEditor(JSON.stringify({ [key]: [{ [field]: 'First' }, { [field]: 'Second' }] }));
        let saved;
        editor.$watch = () => {};
        editor.init({ get: () => type, set: (name, value) => { saved = { name, value }; } });
        editor.data[key][0][field] = 'Edited first';
        editor.moveItemIn(editor.data[key], 0, 1);
        editor.serialize();
        assert.equal(saved.name, 'blockDataRaw');
        assert.deepEqual(JSON.parse(saved.value)[key], [{ [field]: 'Second' }, { [field]: 'Edited first' }]);
    });
}

test('an intro-only text block supports nested CTA editing', () => {
    const editor = editorContext.mgeBlockEditor('{"intro":"Existing intro"}');
    let saved;
    editor.$watch = () => {};
    editor.init({ get: () => 'text', set: (name, value) => { saved = value; } });
    editor.data.cta.label = 'Contact';
    editor.data.cta.href = 'mailto:partner@example.test';
    editor.serialize();
    assert.deepEqual(JSON.parse(saved), {
        intro: 'Existing intro', cta: { label: 'Contact', href: 'mailto:partner@example.test', icon: '' },
    });
});
