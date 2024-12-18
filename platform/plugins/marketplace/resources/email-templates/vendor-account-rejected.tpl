{{ header }}

<div class="bb-main-content">
    <table class="bb-box" cellpadding="0" cellspacing="0">
        <tbody>
        <tr>
            <td class="bb-content bb-pb-0" align="center">
                <table class="bb-icon bb-icon-lg bb-bg-red" cellspacing="0" cellpadding="0">
                    <tbody>
                    <tr>
                        <td valign="middle" align="center">
                            <img src="{{ 'cross' | icon_url }}" class="bb-va-middle" width="40" height="40" alt="Icon" />
                        </td>
                    </tr>
                    </tbody>
                </table>
                <h1 class="bb-text-center bb-m-0 bb-mt-md">Vendor Account Rejected</h1>
            </td>
        </tr>
        <tr>
            <td class="bb-content bb-pb-0">
                <p>Dear {{ store_name }},</p>
                <div>We regret to inform you that your account has not been approved for selling on our site <a href="{{ site_url }}">{{ site_url }}</a>.</div>
            </td>
        </tr>
        <tr>
            <td class="bb-content bb-text-center">
                <div>Thank you for your understanding.</div>
            </td>
        </tr>
        </tbody>
    </table>
</div>

{{ footer }}
