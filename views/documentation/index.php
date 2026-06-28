<div class="box panel mailroom-docs">
    <div class="panel-body">
        <p class="mailroom-docs__lede">
            Mailroom routes ExpressionEngine email through configurable transports, logs send attempts, captures provider responses, supports dev/staging safety modes, and gives admins one place to test and diagnose transactional mail.
        </p>

        <div class="mailroom-docs__toc">
            <a href="#install">Installation</a>
            <a href="#configure">Configuration</a>
            <a href="#usage">Usage</a>
            <a href="#transports">Transports</a>
            <a href="#logging">Logging</a>
            <a href="#webhooks">Webhooks</a>
            <a href="#troubleshooting">Troubleshooting</a>
        </div>
    </div>
</div>

<div id="install" class="box panel mailroom-docs">
    <div class="panel-heading">
        <h2>Installation</h2>
    </div>
    <div class="panel-body">
        <ol>
            <li>Place the add-on folder at <code>system/user/addons/mailroom</code>. The folder must be named <code>mailroom</code>, not <code>mailroom-main</code>.</li>
            <li>In the ExpressionEngine Control Panel, go to Add-Ons and install Mailroom.</li>
            <li>If deploying an update, run <code>php system/ee/eecli.php addons:update -a mailroom</code> from the site root when CLI access is available.</li>
            <li>Clear ExpressionEngine caches after install or update. Some hosts may show cache file permission warnings while still completing the clear.</li>
            <li>Open <a href="<?=$diagnostics_url?>">Diagnostics</a> and confirm the database tables, default transport, and email hook checks pass.</li>
        </ol>
    </div>
</div>

<div id="configure" class="box panel mailroom-docs">
    <div class="panel-heading">
        <h2>Configuration</h2>
    </div>
    <div class="panel-body">
        <ol>
            <li>Open <a href="<?=$transports_url?>">Transports</a>.</li>
            <li>Enable the transport you want to use.</li>
            <li>Mark one enabled transport as Default.</li>
            <li>Click Manage for that transport and enter its provider settings.</li>
            <li>Use Send Test Email from the transport screen before routing real site mail.</li>
            <li>Open <a href="<?=$settings_url?>">Settings</a> and enable Route ExpressionEngine email through Mailroom when you are ready for EE email sends to use Mailroom.</li>
        </ol>

        <h3>Core Settings</h3>
        <ul>
            <li><strong>Default Transport:</strong> the transport Mailroom uses for routed ExpressionEngine email.</li>
            <li><strong>Default From Email/Name/Reply-To:</strong> fallback sender values used when the original message does not provide them.</li>
            <li><strong>Dev/Staging Mode:</strong> normal sending, capture only, redirect all, or suppress all.</li>
            <li><strong>Route ExpressionEngine email through Mailroom:</strong> enables the ExpressionEngine <code>email_send</code> hook so template forms and core sends flow through Mailroom.</li>
            <li><strong>Allowlist Domains:</strong> domains allowed during capture/redirect rules.</li>
        </ul>
    </div>
</div>

<div id="usage" class="box panel mailroom-docs">
    <div class="panel-heading">
        <h2>Usage</h2>
    </div>
    <div class="panel-body">
        <p>Mailroom does not require new template tags. Existing ExpressionEngine email sends continue to use the normal EE APIs and template tags once routing is enabled.</p>

        <h3>Basic EE Contact Form</h3>
        <pre><code>{exp:email:contact_form
    recipients="you@example.com"
    return="/contact/thank-you"
    charset="utf-8"
}
    &lt;p&gt;
        &lt;label for="name"&gt;Name&lt;/label&gt;
        &lt;input id="name" type="text" name="name" required&gt;
    &lt;/p&gt;

    &lt;p&gt;
        &lt;label for="from"&gt;Email&lt;/label&gt;
        &lt;input id="from" type="email" name="from" required&gt;
    &lt;/p&gt;

    &lt;p&gt;
        &lt;label for="subject"&gt;Subject&lt;/label&gt;
        &lt;input id="subject" type="text" name="subject" value="Website inquiry" required&gt;
    &lt;/p&gt;

    &lt;p&gt;
        &lt;label for="message"&gt;Message&lt;/label&gt;
        &lt;textarea id="message" name="message" rows="8" required&gt;&lt;/textarea&gt;
    &lt;/p&gt;

    &lt;input type="hidden" name="required" value="name|from|subject|message"&gt;
    &lt;button type="submit"&gt;Send&lt;/button&gt;
{/exp:email:contact_form}</code></pre>

        <p>After submission, check <a href="<?=$logs_url?>">Email Log</a>. If nothing appears, confirm routing is enabled in Settings and the email hook passes in Diagnostics.</p>
    </div>
</div>

<div id="transports" class="box panel mailroom-docs">
    <div class="panel-heading">
        <h2>Transports</h2>
    </div>
    <div class="panel-body">
        <h3>Mailpit / Dev Capture</h3>
        <ol>
            <li>Enable Mailpit / Dev Capture in Transports.</li>
            <li>Set the host and port. For local DDEV this is usually <code>127.0.0.1</code> and <code>1025</code>.</li>
            <li>Use this for local testing when you want to capture mail without delivering it to real recipients.</li>
        </ol>

        <h3>Generic SMTP</h3>
        <ol>
            <li>Enter the SMTP host, port, encryption, username, and password provided by the mailbox host.</li>
            <li>Set Default From Email to the authenticated mailbox or an address that provider explicitly allows that mailbox to send as.</li>
            <li>Use port <code>587</code> with TLS or port <code>465</code> with SSL depending on the provider instructions.</li>
            <li>If the provider says the sender is not owned by the user, the From address does not match the authenticated SMTP account or an approved alias.</li>
        </ol>

        <h3>Microsoft 365 Graph</h3>
        <ol>
            <li>In Azure, create an app registration for Mailroom.</li>
            <li>Add Microsoft Graph application permission <code>Mail.Send</code>.</li>
            <li>Grant admin consent for the tenant.</li>
            <li>Create a client secret.</li>
            <li>In Mailroom, enter Tenant ID, Client ID, Client Secret, and Sender Mailbox.</li>
            <li>The sender mailbox must exist and be allowed by the tenant policies. The test button sends through Graph <code>/users/{sender}/sendMail</code>.</li>
        </ol>

        <h3>Google Gmail API</h3>
        <p>Google's setup is the long hallway with every door labeled "almost done." The working Mailroom path is Google Workspace service account authentication with domain-wide delegation.</p>
        <ol>
            <li>Open Google Cloud Console and create or select a project for Mailroom.</li>
            <li>Enable the Gmail API for that project.</li>
            <li>Create a service account, such as <code>Mailroom Sender</code>.</li>
            <li>Open the service account details and enable Domain-wide Delegation.</li>
            <li>Copy the service account Client ID. This is the long numeric ID, not the service account email.</li>
            <li>Open Google Workspace Admin Console as a super admin.</li>
            <li>Go to Security, Access and data control, API controls, Domain-wide delegation.</li>
            <li>Add a new domain-wide delegation entry using the numeric Client ID.</li>
            <li>Use this exact OAuth scope: <code>https://www.googleapis.com/auth/gmail.send</code>.</li>
            <li>Back in Google Cloud, open the service account Keys tab and create a JSON key.</li>
            <li>In Mailroom, Service Account Email is the JSON <code>client_email</code> value.</li>
            <li>In Mailroom, Service Account Private Key is the JSON <code>private_key</code> value, including <code>BEGIN PRIVATE KEY</code> and <code>END PRIVATE KEY</code>.</li>
            <li>In Mailroom, Delegated Sender Mailbox is the real Google Workspace mailbox to send as, such as <code>me@example.com</code>.</li>
        </ol>

        <p>Do not paste <code>private_key_id</code> into the private key field. That value is just a label. If a private key is ever exposed in a screenshot or chat, delete that key in Google Cloud and create a new one.</p>
    </div>
</div>

<div id="logging" class="box panel mailroom-docs">
    <div class="panel-heading">
        <h2>Logging And Privacy</h2>
    </div>
    <div class="panel-body">
        <ul>
            <li><strong>Metadata and provider response:</strong> stores delivery status, recipient metadata, transport, provider response, and diagnostics.</li>
            <li><strong>Full email body logging:</strong> also stores the rendered email body. Use only when the content is safe to retain.</li>
            <li><strong>Privacy mode:</strong> limits sensitive stored fields where supported.</li>
            <li><strong>Mask recipients in log views:</strong> masks recipient addresses in Control Panel views while preserving useful debugging context.</li>
            <li><strong>Bulk log actions:</strong> use Email Log checkboxes to delete selected log rows or only delete stored message bodies.</li>
        </ul>
    </div>
</div>

<div id="webhooks" class="box panel mailroom-docs">
    <div class="panel-heading">
        <h2>Provider Webhooks</h2>
    </div>
    <div class="panel-body">
        <p>Mailroom includes scaffolded provider webhook storage for future delivery, bounce, suppression, delay, open, and click events. Provider-specific signature verification and event mapping are intentionally limited until each provider integration is locked down.</p>
        <ol>
            <li>Enable Provider Event Webhooks in Settings only when you have a provider integration ready to send events.</li>
            <li>Set a Webhook Shared Secret.</li>
            <li>Use the generated Webhook Endpoint URL as the provider target.</li>
            <li>Provider requests must include the secret as the <code>secret</code> request parameter or <code>X-Mailroom-Secret</code> header.</li>
        </ol>
    </div>
</div>

<div id="troubleshooting" class="box panel mailroom-docs">
    <div class="panel-heading">
        <h2>Troubleshooting</h2>
    </div>
    <div class="panel-body">
        <ul>
            <li>If transport tests work but forms do not log, make sure Route ExpressionEngine email through Mailroom is enabled and Diagnostics shows the email hook passing.</li>
            <li>If the default transport dropdown is empty, enable at least one transport on the Transports screen.</li>
            <li>If SMTP rejects the sender, align the From address with the authenticated SMTP user or an approved alias.</li>
            <li>If Microsoft Graph fails with an auth error, recheck tenant ID, client ID, client secret, app permissions, and admin consent.</li>
            <li>If Google fails with <code>unauthorized_client</code> or <code>invalid_grant</code>, recheck domain-wide delegation, the numeric Client ID, the Gmail scope, and the delegated sender mailbox.</li>
            <li>If Google says the private key cannot be parsed, paste the full JSON <code>private_key</code> value, not <code>private_key_id</code>.</li>
            <li>Use Diagnostics after every deployment. It is faster than guessing, and less likely to make you question your career choices.</li>
        </ul>
    </div>
</div>

<style>
    .mailroom-docs {
        max-width: 1180px;
    }

    .mailroom-docs__lede {
        max-width: 900px;
        margin-bottom: 14px;
        font-size: 15px;
        line-height: 1.55;
    }

    .mailroom-docs__toc {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .mailroom-docs__toc a {
        display: inline-block;
        padding: 6px 10px;
        border: 1px solid #dfe3e6;
        border-radius: 4px;
        background: #f8fafb;
        text-decoration: none;
    }

    .mailroom-docs h3 {
        margin-top: 18px;
    }

    .mailroom-docs li {
        margin-bottom: 7px;
        line-height: 1.45;
    }

    .mailroom-docs pre {
        overflow-x: auto;
        padding: 14px;
        border: 1px solid #dfe3e6;
        border-radius: 4px;
        background: #f8fafb;
    }

    .mailroom-docs code {
        font-family: SFMono-Regular, Consolas, Liberation Mono, Menlo, monospace;
        font-size: 12px;
    }
</style>
