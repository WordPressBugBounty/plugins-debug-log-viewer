<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Debug Log Viewer Cleanup Report</title>
</head>

<body style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 1.6; margin: 0; padding: 0; background-color: #f5f7fb;">

    <table style="border-collapse: collapse; width: 100%; background-color: #f5f7fb;">
        <tr>
            <td style="padding: 30px 20px;">

                <table style="border-collapse: collapse; margin: 0 auto; max-width: 620px; width: 100%;">

                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #0d6efd 0%, #4c84ff 100%); border-radius: 10px 10px 0 0; padding: 28px 36px;">
                            <table style="border-collapse: collapse; width: 100%;">
                                <tr>
                                    <td>
                                        <p style="color: #a9c2ff; font-size: 11px; font-weight: 600; letter-spacing: 1.5px; margin: 0 0 4px 0; text-transform: uppercase;">WordPress Plugin</p>
                                        <h1 style="color: #ffffff; font-size: 22px; font-weight: 700; margin: 0; line-height: 1.2;">
                                            Debug Log <span style="color: #a9c2ff;">Viewer</span>
                                        </h1>
                                    </td>
                                    <td style="text-align: right; vertical-align: middle;">
                                        <span style="background-color: rgba(255,255,255,0.15); border-radius: 20px; color: #ffffff; font-size: 12px; font-weight: 600; padding: 5px 14px;">Cleanup Report</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Subheader bar -->
                    <tr>
                        <td style="background-color: rgba(13, 110, 253, 0.12); padding: 10px 36px;">
                            <p style="color: #0d47cc; font-size: 12px; margin: 0;">
                                Cleanup performed on <a href="{{dbg_lv_website}}" style="color: #0d6efd; font-weight: 600; text-decoration: none;">{{dbg_lv_website}}</a>
                                &nbsp;&mdash;&nbsp; {{dbg_lv_generated_date}}
                            </p>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="background-color: #ffffff; padding: 28px 36px;">

                            <p style="color: #4c4c4c; font-size: 14px; margin: 0 0 20px 0;">
                                The debug log file on <strong>{{dbg_lv_site_name}}</strong> was automatically cleaned up. Here is a summary of what happened.
                            </p>

                            <!-- Details table -->
                            <table style="border-collapse: collapse; border-radius: 6px; font-size: 13px; overflow: hidden; width: 100%;">
                                <thead>
                                    <tr>
                                        <th style="background-color: #f0f4ff; border-bottom: 2px solid #d0e0ff; color: #0d6efd; font-size: 11px; font-weight: 700; letter-spacing: 0.5px; padding: 10px 12px; text-align: left; text-transform: uppercase;">Detail</th>
                                        <th style="background-color: #f0f4ff; border-bottom: 2px solid #d0e0ff; color: #0d6efd; font-size: 11px; font-weight: 700; letter-spacing: 0.5px; padding: 10px 12px; text-align: left; text-transform: uppercase;">Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td style="border-bottom: 1px solid #e8eef8; color: #4c4c4c; font-weight: 600; padding: 10px 12px; vertical-align: top;">Size Before</td>
                                        <td style="border-bottom: 1px solid #e8eef8; color: #4c4c4c; padding: 10px 12px; vertical-align: top;">{{dbg_lv_old_size}}</td>
                                    </tr>
                                    <tr>
                                        <td style="background-color: #f7f9ff; border-bottom: 1px solid #e8eef8; color: #4c4c4c; font-weight: 600; padding: 10px 12px; vertical-align: top;">Size After</td>
                                        <td style="background-color: #f7f9ff; border-bottom: 1px solid #e8eef8; color: #4c4c4c; padding: 10px 12px; vertical-align: top;">{{dbg_lv_new_size}}</td>
                                    </tr>
                                    <tr>
                                        <td style="border-bottom: 1px solid #e8eef8; color: #4c4c4c; font-weight: 600; padding: 10px 12px; vertical-align: top;">Reduction</td>
                                        <td style="border-bottom: 1px solid #e8eef8; color: #2d8a4e; font-weight: 700; padding: 10px 12px; vertical-align: top;">{{dbg_lv_reduction}}</td>
                                    </tr>
                                    <tr>
                                        <td style="background-color: #f7f9ff; border-bottom: 1px solid #e8eef8; color: #4c4c4c; font-weight: 600; padding: 10px 12px; vertical-align: top;">Method</td>
                                        <td style="background-color: #f7f9ff; border-bottom: 1px solid #e8eef8; color: #4c4c4c; padding: 10px 12px; vertical-align: top;">{{dbg_lv_method}}</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #4c4c4c; font-weight: 600; padding: 10px 12px; vertical-align: top;">Performed At</td>
                                        <td style="color: #4c4c4c; padding: 10px 12px; vertical-align: top;">{{dbg_lv_time}}</td>
                                    </tr>
                                </tbody>
                            </table>

                            <p style="color: #6c757d; font-size: 13px; margin: 24px 0 0 0; text-align: center;">
                                This is an automated notification from the Debug Log Viewer plugin.
                            </p>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #0d6efd 0%, #4c84ff 100%); border-radius: 0 0 10px 10px; padding: 20px 36px; text-align: center;">
                            <p style="color: #ffffff; font-size: 13px; margin: 0 0 6px 0;">
                                Questions? Contact us at <a href="mailto:sanchoclo@gmail.com" style="color: #ffffff; text-decoration: underline;">sanchoclo@gmail.com</a>
                            </p>
                            <p style="color: rgba(255,255,255,0.75); font-size: 11px; margin: 0;">
                                To stop receiving these emails, disable cleanup notifications on the plugin settings page.
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>

</html>
